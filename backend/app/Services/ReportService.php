<?php

namespace App\Services;

use App\Models\ReportTemplate;
use App\Models\LaboratoryRequest;

class ReportService
{
    /**
     * Prepare hydrated data for template and sample id.
     * 
     * Returns array with:
     * - schema: The JSON schema with data bindings resolved
     * - data: The flattened data for rendering
     */
    public function prepareDataForTemplate(ReportTemplate $template, $sampleId): array
    {
        // Load the laboratory request with all relationships
        $lab = LaboratoryRequest::with([
            'patient',
            'results.test',
            'results.testParameter',
            'doctor',
            'sample'
        ])->find($sampleId);

        if (!$lab) {
            abort(404, 'Laboratory request not found');
        }

        // Build data context for the schema
        $data = $this->buildDataContext($lab);

        // Get the template schema
        $schema = $template->schema_data ?? [];

        // Hydrate schema with actual data (replace {{field}} placeholders)
        $hydratedSchema = $this->hydrateSchema($schema, $data);

        // Process conditional formatting
        $processedSchema = $this->applyConditionalFormatting($hydratedSchema, $data);

        return [
            'schema' => $processedSchema,
            'data' => $data,
        ];
    }

    /**
     * Build complete data context from laboratory request
     */
    private function buildDataContext($lab): array
    {
        $patient = $lab->patient;
        
        $data = [
            // Patient information
            'patient' => [
                'id' => $patient->id ?? null,
                'pid' => $patient->patient_id ?? null,
                'name' => $this->getFullName($patient),
                'first_name' => $patient->first_name ?? null,
                'last_name' => $patient->last_name ?? null,
                'date_of_birth' => $patient->date_of_birth ?? null,
                'age' => $this->calculateAge($patient->date_of_birth ?? null),
                'gender' => $patient->gender ?? null,
                'phone' => $patient->phone ?? null,
                'email' => $patient->email ?? null,
            ],

            // Sample/Request information
            'sample' => [
                'id' => $lab->id ?? null,
                'request_id' => $lab->request_code ?? null,
                'collected_at' => $lab->date_ordered ?? null,
                'processed_at' => $lab->date_received ?? null,
                'reported_at' => now()->toDateTimeString(),
            ],

            // Lab facility information
            'lab' => [
                'name' => config('app.name', 'Laboratory'),
                'address' => config('app.address', ''),
                'phone' => config('app.phone', ''),
                'email' => config('app.email', ''),
            ],

            // Referring doctor
            'doctor' => $lab->doctor ? [
                'name' => $lab->doctor->name ?? null,
                'code' => $lab->doctor->code ?? null,
                'specialization' => $lab->doctor->specialization ?? null,
            ] : [],

            // Test results array (for data tables)
            'tests' => $this->formatTestResults($lab->results),

            // Grouped test results by category
            'tests_by_category' => $this->groupTestsByCategory($lab->results),

            // Pathologist/authorized personnel (for signatures)
            'pathologist' => auth()->user() ? [
                'name' => auth()->user()->name ?? null,
                'role' => auth()->user()->role ?? 'Pathologist',
                'signature_path' => auth()->user()->signature_path ?? null,
            ] : [],
        ];

        return $data;
    }

    /**
     * Format test results for rendering
     */
    private function formatTestResults($results)
    {
        return $results->map(function ($result) {
            return [
                'id' => $result->id,
                'test_name' => $result->test->name ?? $result->testParameter->name ?? 'Test',
                'test_code' => $result->test->code ?? null,
                'category' => $result->test->category_name ?? $result->testParameter->category ?? 'General',
                'result_value' => $result->result_value,
                'unit' => $result->unit,
                'ref_range' => $result->reference_range,
                'ref_low' => $result->reference_low,
                'ref_high' => $result->reference_high,
                'flag' => $result->flag,
                'status' => $this->calculateResultStatus(
                    $result->result_value,
                    $result->reference_low,
                    $result->reference_high,
                    $result->flag
                ),
            ];
        })->toArray();
    }

    /**
     * Group test results by category
     */
    private function groupTestsByCategory($results)
    {
        $grouped = [];
        foreach ($results as $result) {
            $category = $result->test->category_name ?? $result->testParameter->category ?? 'General';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = [
                'test_name' => $result->test->name ?? $result->testParameter->name ?? 'Test',
                'result_value' => $result->result_value,
                'unit' => $result->unit,
                'ref_range' => $result->reference_range,
                'ref_low' => $result->reference_low,
                'ref_high' => $result->reference_high,
                'flag' => $result->flag,
            ];
        }
        return $grouped;
    }

    /**
     * Calculate status based on result value and reference ranges
     */
    private function calculateResultStatus($value, $low, $high, $flag)
    {
        if ($flag === 'H' || $flag === 'HIGH') {
            return 'high';
        }
        if ($flag === 'L' || $flag === 'LOW') {
            return 'low';
        }

        $numValue = floatval($value);
        $numLow = floatval($low);
        $numHigh = floatval($high);

        if ($numValue > $numHigh) {
            return 'high';
        } elseif ($numValue < $numLow) {
            return 'low';
        }

        return 'normal';
    }

    /**
     * Hydrate schema with actual data
     * Replaces {{field}} placeholders with real values
     */
    private function hydrateSchema($schema, $data): array
    {
        // Convert schema to JSON string, do replacements, convert back
        $json = json_encode($schema);

        // Replace {{field}} placeholders with data values
        $json = preg_replace_callback(
            '/\{\{([a-zA-Z0-9_.]+)\}\}/',
            function ($matches) use ($data) {
                $field = $matches[1];
                $value = $this->getNestedValue($data, $field);
                return addslashes($value ?? '');
            },
            $json
        );

        return json_decode($json, true) ?? $schema;
    }

    /**
     * Apply conditional formatting rules to schema
     * Add styles based on result values
     */
    private function applyConditionalFormatting($schema, $data): array
    {
        if (!isset($schema['elements'])) {
            return $schema;
        }

        $schema['elements'] = array_map(function ($element) use ($data) {
            return $this->processElementConditionalFormatting($element, $data);
        }, $schema['elements']);

        return $schema;
    }

    /**
     * Recursively process conditional formatting for elements
     */
    private function processElementConditionalFormatting($element, $data)
    {
        // If this is a datatable with conditionalColor columns
        if ($element['type'] === 'datatable') {
            // Mark columns that need conditional coloring
            if (isset($element['columns'])) {
                foreach ($element['columns'] as &$col) {
                    if ($col['field'] === 'result_value') {
                        $col['conditionalColor'] = true;
                    }
                }
            }
        }

        // Recursively process children
        if (isset($element['children']) && is_array($element['children'])) {
            $element['children'] = array_map(
                fn($child) => $this->processElementConditionalFormatting($child, $data),
                $element['children']
            );
        }

        return $element;
    }

    /**
     * Get nested value from array using dot notation
     * Example: getNestedValue($data, 'patient.name')
     */
    private function getNestedValue($data, $path)
    {
        $parts = explode('.', $path);
        $value = $data;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Get full name from patient
     */
    private function getFullName($patient): string
    {
        $parts = array_filter([
            $patient->first_name ?? null,
            $patient->last_name ?? null,
        ]);

        return implode(' ', $parts) ?: ($patient->name ?? 'Patient');
    }

    /**
     * Calculate age from date of birth
     */
    private function calculateAge($dob): ?int
    {
        if (!$dob) {
            return null;
        }

        try {
            $birthDate = new \DateTime($dob);
            $today = new \DateTime();
            $age = $today->diff($birthDate)->y;
            return $age;
        } catch (\Exception $e) {
            return null;
        }
    }
}

