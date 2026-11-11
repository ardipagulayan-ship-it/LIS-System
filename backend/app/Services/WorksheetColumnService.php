<?php

namespace App\Services;

use App\Models\TestCategory;
use App\Models\Test;
use App\Models\TestParameter;
use Illuminate\Support\Collection;

class WorksheetColumnService
{
    /**
     * Get dynamic columns for a test category based on actual test parameters in the database
     *
     * @param int $categoryId
     * @return array
     */
    public function getDynamicColumns(int $categoryId): array
    {
        try {
            \Log::info('Getting dynamic columns for category ID: ' . $categoryId);
            
            // Get all test parameters for tests in this category
            $testParameters = TestParameter::whereHas('test', function ($query) use ($categoryId) {
                $query->where('test_category_id', $categoryId);
            })
            ->with(['test'])
            ->get();

            \Log::info('Found ' . $testParameters->count() . ' test parameters for category ' . $categoryId);

            // Group parameters by their names and get unique parameters
            $uniqueParameters = $testParameters->unique(function ($parameter) {
                $name = $parameter->test_name ?? $parameter->name ?? '';
                return strtolower(trim($name));
            });

            // Convert to column format
            $columns = [];
            foreach ($uniqueParameters as $parameter) {
                $paramName = $parameter->test_name ?? $parameter->name ?? '';
                if (empty($paramName)) continue;

                // Create a safe key for the column
                $key = $this->createColumnKey($paramName);
                $label = $this->createColumnLabel($paramName);

                $columns[] = [
                    'key' => $key,
                    'label' => $label,
                    'width' => $this->calculateColumnWidth($label),
                    'parameter_id' => $parameter->id,
                    'parameter_name' => $paramName,
                    'unit' => $parameter->unit
                ];
            }

            // Sort columns alphabetically by label
            usort($columns, function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            });

            return $columns;

        } catch (\Exception $e) {
            \Log::error('Error generating dynamic columns: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get test results data with dynamic columns for a specific category
     *
     * @param int $categoryId
     * @param Collection $testResults
     * @return array
     */
    public function getTestResultColumns(int $categoryId, Collection $testResults): array
    {
        $columns = [];

        // Get the dynamic column definitions
        $columnDefinitions = $this->getDynamicColumns($categoryId);

        // Initialize all columns with null values
        foreach ($columnDefinitions as $column) {
            $columns[$column['key']] = null;
        }

        // Populate with actual test results
        foreach ($testResults as $result) {
            $parameter = $result->testParameter;
            if (!$parameter) continue;

            $paramName = $parameter->test_name ?? $parameter->name ?? '';
            if (empty($paramName)) continue;

            $key = $this->createColumnKey($paramName);

            // Find matching column and set the value
            if (array_key_exists($key, $columns)) {
                $columns[$key] = $result->result_value;
            }
        }

        return $columns;
    }

    /**
     * Create a safe column key from parameter name
     *
     * @param string $paramName
     * @return string
     */
    private function createColumnKey(string $paramName): string
    {
        // Convert to lowercase, replace spaces and special chars with underscores
        $key = strtolower(trim($paramName));
        $key = preg_replace('/[^a-z0-9\s]/', '', $key);
        $key = preg_replace('/\s+/', '_', $key);
        
        // Ensure key is not empty and not too long
        if (empty($key)) {
            $key = 'parameter_' . uniqid();
        }
        
        return substr($key, 0, 50); // Limit length
    }

    /**
     * Create a display label from parameter name
     *
     * @param string $paramName
     * @return string
     */
    private function createColumnLabel(string $paramName): string
    {
        // Clean up the parameter name
        $label = trim($paramName);
        
        // Convert common abbreviations to uppercase
        $label = strtoupper($label);
        
        // Handle common variations
        $label = str_replace(['WHITE BLOOD CELL', 'WHITE BLOOD CELLS'], 'WBC', $label);
        $label = str_replace(['RED BLOOD CELL', 'RED BLOOD CELLS'], 'RBC', $label);
        $label = str_replace(['HEMOGLOBIN', 'HAEMOGLOBIN'], 'HGB', $label);
        $label = str_replace(['HEMATOCRIT', 'HAEMATOCRIT'], 'HCT', $label);
        $label = str_replace(['PLATELET', 'PLATELETS'], 'PLT', $label);
        
        return $label;
    }

    /**
     * Calculate appropriate column width based on label length
     *
     * @param string $label
     * @return int
     */
    private function calculateColumnWidth(string $label): int
    {
        $baseWidth = 80;
        $labelLength = strlen($label);
        
        // Adjust width based on label length
        if ($labelLength <= 3) {
            return 60;
        } elseif ($labelLength <= 6) {
            return 80;
        } elseif ($labelLength <= 10) {
            return 100;
        } else {
            return min(120, $labelLength * 8);
        }
    }

    /**
     * Get column metadata for frontend
     *
     * @param int $categoryId
     * @return array
     */
    public function getColumnMetadata(int $categoryId): array
    {
        $columns = $this->getDynamicColumns($categoryId);
        
        return [
            'columns' => $columns,
            'total_columns' => count($columns),
            'category_id' => $categoryId
        ];
    }
}
