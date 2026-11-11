<?php

namespace App\Services;

use App\Models\TestResult;
use App\Models\LaboratoryRequest;
use App\Models\ReportGroup;
use App\Models\ReportLayout;
use Illuminate\Support\Collection;

class PrintService
{
    /**
     * Group test results by report_group_id.
     * Returns an array of groups with their parameters and layout info.
     */
    public function groupResultsByReportGroup(LaboratoryRequest $request): array
    {
        $results = TestResult::where('laboratory_request_id', $request->id)
            ->with([
                'testParameter' => function ($query) {
                    $query->with([
                        'reportGroup' => function ($q) {
                            $q->with('layout');
                        },
                        'test'
                    ]);
                }
            ])
            ->get();

        \Log::info('Total results fetched: ' . $results->count());

        $grouped = [];

        foreach ($results as $result) {
            $param = $result->testParameter;
            if (!$param) {
                \Log::warning('Result has no testParameter: ' . $result->id);
                continue;
            }

            // Determine group ID: use report_group_id if set, otherwise use category's default or null
            $groupId = $param->report_group_id ?? 'default';

            if (!isset($grouped[$groupId])) {
                $layout = null;
                $groupName = 'Unknown Group';

                if ($groupId !== 'default' && $param->reportGroup) {
                    $groupName = $param->reportGroup->name ?? 'Unknown Group';
                    $layout = $param->reportGroup->layout;
                } else {
                    $groupName = 'Default Results';
                }

                $grouped[$groupId] = [
                    'group_id' => $groupId,
                    'group_name' => $groupName,
                    'layout_id' => $groupId === 'default' ? null : ($param->reportGroup->report_layout_id ?? null),
                    'layout' => $layout,
                    'results' => [],
                    'sort_order' => $groupId === 'default' ? 999 : ($param->reportGroup->sort_order ?? 999)
                ];

                \Log::info('Created group', ['group_id' => $groupId, 'group_name' => $groupName, 'has_layout' => $layout ? true : false]);
            }

            $grouped[$groupId]['results'][] = [
                'id' => $result->id,
                'test_parameter_id' => $param->id,
                'test_parameter_name' => $param->test_name ?? $param->name ?? 'Unknown',
                'result_value' => $result->result_value,
                'unit' => $result->unit,
                'flag' => $result->flag,
                'equipment_id' => $result->equipment_id,
                'result_comment' => $result->result_comment,
                'created_at' => $result->created_at,
                'test_name' => $param->test->name ?? 'Unknown Test'
            ];
        }

        // Sort groups by sort_order
        $sorted = collect($grouped)->sortBy('sort_order')->values()->toArray();

        \Log::info('Total groups: ' . count($sorted), [
            'groups' => array_column($sorted, 'group_id')
        ]);

        return $sorted;
    }

    /**
     * Render HTML for a single report group using its layout.
     */
    public function renderGroupHtml($group, $request): string
    {
        // If group has a layout, use the layout_json to render
        if ($group['layout']) {
            return $this->renderWithLayout($group, $request);
        }

        // Fallback: render a simple table
        return $this->renderSimpleTable($group, $request);
    }

    private function renderWithLayout($group, $request): string
    {
        $layout = $group['layout'];

        // New preferred fields: sections_json and page_config (backward compatible with layout_json)
        $layoutSections = $layout->sections_json ?? null;
        $layoutJson = $layout->layout_json ?? null;

        if (is_string($layoutSections)) {
            $decoded = json_decode($layoutSections, true);
            $layoutSections = $decoded ?? $layoutSections;
        }

        if (is_string($layoutJson)) {
            $decoded = json_decode($layoutJson, true);
            $layoutJson = $decoded ?? $layoutJson;
        }

        // Build HTML from layout sections
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
        $html .= 'body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; } ';
        $html .= 'table { width: 100%; border-collapse: collapse; } ';
        $html .= 'th, td { padding: 8px; border: 1px solid #ccc; text-align: left; } ';
        $html .= 'th { background-color: #f0f0f0; font-weight: bold; } ';
        $html .= '</style></head><body>';
        // If sections_json exists use it; otherwise fall back to layoutJson
        $layoutData = $layoutSections ?? $layoutJson ?? [];
        $html .= $this->renderLayoutBlocks($layoutData, $group, $request);
        $html .= '</body></html>';

        return $html;
    }

    private function renderLayoutBlocks($layoutData, $group, $request): string
    {
        $html = '';

        if (is_string($layoutData)) {
            $layoutData = json_decode($layoutData, true);
        }

        // Extract sections and configuration
        $sections = $layoutData['sections'] ?? [];
        $headerConfig = $layoutData['header'] ?? [];
        $patientInfoConfig = $layoutData['patient_info'] ?? [];
        $resultsConfig = $layoutData['results'] ?? [];
        $signatories = $layoutData['signatories'] ?? [];

        // Normalize sections to array of strings
        if (!is_array($sections)) {
            $sections = [$sections];
        }

        $request_obj = $request;
        $group_obj = $group;

        foreach ($sections as $section) {
            $section = trim($section);

            switch ($section) {
                case 'header':
                    $html .= $this->renderHeader($headerConfig, $group_obj);
                    break;

                case 'patient-info':
                    $html .= $this->renderPatientInfo($patientInfoConfig, $request_obj);
                    break;

                case 'results-table':
                    $html .= $this->renderResultsTable($group_obj['results'], $resultsConfig);
                    break;

                case 'footer':
                    $html .= $this->renderFooter();
                    break;

                case 'signatories':
                    $html .= $this->renderSignatories($signatories);
                    break;

                case 'notes':
                    $html .= $this->renderNotes();
                    break;

                default:
                    break;
            }
        }

        return $html;
    }

    private function renderHeader($config, $group): string
    {
        $html = '<div style="border-bottom: 2px solid #000; margin-bottom: 12px; padding-bottom: 8px;">';
        
        // Header with logos and title
        $leftLogo = $config['left_logo'] ?? '';
        $title = $config['title'] ?? 'Laboratory Report';
        $rightLogo = $config['right_logo'] ?? '';

        $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
        
        if ($leftLogo) {
            $html .= '<div style="width: 80px;"><img src="' . htmlspecialchars($leftLogo) . '" style="max-width: 100%; height: auto;" alt="Logo"></div>';
        } else {
            $html .= '<div style="width: 80px;"></div>';
        }
        
        $html .= '<div style="flex: 1; text-align: center;">';
        $html .= '<h2 style="margin: 0; font-size: 24px;">' . htmlspecialchars($title) . '</h2>';
        $html .= '<p style="margin: 4px 0 0 0; font-size: 14px; color: #666;">' . htmlspecialchars($group['group_name'] ?? '') . '</p>';
        $html .= '</div>';
        
        if ($rightLogo) {
            $html .= '<div style="width: 80px; text-align: right;"><img src="' . htmlspecialchars($rightLogo) . '" style="max-width: 100%; height: auto;" alt="Logo"></div>';
        } else {
            $html .= '<div style="width: 80px;"></div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function renderPatientInfo($config, $request): string
    {
        $patient = $request->patient;
        if (!$patient) {
            return '';
        }

        $html = '<div style="margin-bottom: 12px; background: #f9f9f9; padding: 8px; border-left: 3px solid #007bff;">';
        
        $fields = $config['fields'] ?? ['patient_id', 'last_name', 'first_name'];
        $columns = $config['columns'] ?? 2;
        
        $fieldMap = [
            'patient_id' => ['label' => 'Patient ID', 'value' => $patient->id],
            'last_name' => ['label' => 'Last Name', 'value' => $patient->last_name],
            'first_name' => ['label' => 'First Name', 'value' => $patient->first_name],
            'dob' => ['label' => 'DOB', 'value' => $patient->date_of_birth],
            'gender' => ['label' => 'Gender', 'value' => $patient->gender],
            'mrn' => ['label' => 'MRN', 'value' => $patient->mrn ?? 'N/A'],
        ];

        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        
        $fieldCount = 0;
        $html .= '<tr>';
        
        foreach ($fields as $fieldKey) {
            if (!isset($fieldMap[$fieldKey])) {
                continue;
            }
            
            if ($fieldCount > 0 && $fieldCount % $columns === 0) {
                $html .= '</tr><tr>';
            }
            
            $field = $fieldMap[$fieldKey];
            $colWidth = (100 / $columns);
            $html .= '<td style="width: ' . $colWidth . '%; padding: 4px; padding-right: 12px;">';
            $html .= '<strong>' . htmlspecialchars($field['label']) . ':</strong> ' . htmlspecialchars($field['value'] ?? 'N/A');
            $html .= '</td>';
            
            $fieldCount++;
        }
        
        // Fill remaining cells if not full row
        while ($fieldCount % $columns !== 0) {
            $html .= '<td style="width: ' . (100 / $columns) . '%; padding: 4px;"></td>';
            $fieldCount++;
        }
        
        $html .= '</tr></table>';
        $html .= '</div>';

        return $html;
    }

    private function renderResultsTable($results, $config): string
    {
        if (empty($results)) {
            return '<p style="color: #999; font-style: italic;">No results to display</p>';
        }

        $showUnits = $config['show_units'] ?? true;
        $showRefRange = $config['show_ref_range'] ?? true;
        $showFlags = $config['show_flags'] ?? true;

        $html = '<table style="width:100%; border-collapse:collapse; margin-bottom:12px;">';
        $html .= '<thead><tr style="background-color:#f0f0f0; border-bottom:2px solid #333;">';
        $html .= '<th style="padding:8px; text-align:left; border: 1px solid #ccc; font-weight: bold;">Parameter</th>';
        $html .= '<th style="padding:8px; text-align:center; border: 1px solid #ccc; font-weight: bold;">Result</th>';
        
        if ($showUnits) {
            $html .= '<th style="padding:8px; text-align:center; border: 1px solid #ccc; font-weight: bold;">Unit</th>';
        }
        if ($showRefRange) {
            $html .= '<th style="padding:8px; text-align:center; border: 1px solid #ccc; font-weight: bold;">Ref Range</th>';
        }
        if ($showFlags) {
            $html .= '<th style="padding:8px; text-align:center; border: 1px solid #ccc; font-weight: bold;">Flag</th>';
        }
        
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        foreach ($results as $result) {
            $paramName = htmlspecialchars($result['test_parameter_name'] ?? 'N/A');
            $resultValue = htmlspecialchars($result['result_value'] ?? '-');
            $unit = htmlspecialchars($result['unit'] ?? '-');
            $flag = htmlspecialchars($result['flag'] ?? '');

            $html .= '<tr style="border: 1px solid #ccc;">';
            $html .= '<td style="padding:8px; border: 1px solid #ccc;">' . $paramName . '</td>';
            $html .= '<td style="padding:8px; text-align:center; border: 1px solid #ccc;">' . $resultValue . '</td>';
            
            if ($showUnits) {
                $html .= '<td style="padding:8px; text-align:center; border: 1px solid #ccc;">' . $unit . '</td>';
            }
            if ($showRefRange) {
                $html .= '<td style="padding:8px; text-align:center; border: 1px solid #ccc;">N/A</td>';
            }
            if ($showFlags) {
                $html .= '<td style="padding:8px; text-align:center; border: 1px solid #ccc;">' . $flag . '</td>';
            }
            
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    private function renderFooter(): string
    {
        $html = '<div style="margin-top: 20px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 11px; color: #666;">';
        $html .= 'Generated on ' . now()->format('Y-m-d H:i:s') . '<br>';
        $html .= 'Report Period: [PERIOD]';
        $html .= '</div>';

        return $html;
    }

    private function renderSignatories($signatories): string
    {
        if (empty($signatories)) {
            return '';
        }

        $html = '<div style="margin-top: 20px; border-top: 1px solid #ccc; padding-top: 12px;">';
        $html .= '<h4 style="margin: 0 0 12px 0;">Signatories</h4>';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        
        foreach ($signatories as $sig) {
            $role = htmlspecialchars($sig['role'] ?? '');
            $name = htmlspecialchars($sig['name'] ?? '');
            
            if (empty($role) && empty($name)) {
                continue; // Skip empty signatories
            }
            
            $html .= '<tr style="border-bottom: 1px solid #ccc;">';
            $html .= '<td style="width: 40%; padding: 8px;">' . $role . '</td>';
            $html .= '<td style="width: 60%; padding: 8px; text-align: center; border-top: 1px solid #000;">' . $name . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    private function renderNotes(): string
    {
        $html = '<div style="margin-top: 12px; padding: 8px; border: 1px dashed #ccc; background: #fafafa;">';
        $html .= '<strong>Clinical Notes:</strong><br>';
        $html .= '<p style="margin: 0; font-size: 12px;">N/A</p>';
        $html .= '</div>';

        return $html;
    }

    private function renderSimpleTable($group, $request): string
    {
        $patient = $request->patient;
        $patientName = htmlspecialchars($patient->last_name . ', ' . $patient->first_name);
        $patientId = htmlspecialchars($patient->id);
        $dob = htmlspecialchars($patient->date_of_birth ?? 'N/A');
        $groupName = htmlspecialchars($group['group_name']);
        $createdAt = htmlspecialchars($request->created_at);

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
        $html .= 'body { font-family: Arial, sans-serif; margin: 20px; } ';
        $html .= '.header { border-bottom: 2px solid #000; margin-bottom: 12px; padding-bottom: 8px; } ';
        $html .= '.header h2 { margin: 0; } ';
        $html .= '.patient-info { margin-bottom: 12px; } ';
        $html .= '.results-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; } ';
        $html .= '.results-table th, .results-table td { padding: 6px; border: 1px solid #ccc; text-align: left; } ';
        $html .= '.results-table th { background-color: #f0f0f0; } ';
        $html .= '.footer { margin-top: 12px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 12px; } ';
        $html .= '</style></head><body>';

        $html .= '<div class="header"><h2>' . $groupName . '</h2></div>';
        $html .= '<div class="patient-info">';
        $html .= '<strong>Patient:</strong> ' . $patientName . '<br>';
        $html .= '<strong>Patient ID:</strong> ' . $patientId . '<br>';
        $html .= '<strong>DOB:</strong> ' . $dob . '<br>';
        $html .= '</div>';

        $html .= '<table class="results-table"><thead><tr>';
        $html .= '<th>Parameter</th><th>Result</th><th>Unit</th><th>Flag</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($group['results'] as $result) {
            $paramName = htmlspecialchars($result['test_parameter_name']);
            $resultValue = htmlspecialchars($result['result_value'] ?? '-');
            $unit = htmlspecialchars($result['unit'] ?? '-');
            $flag = htmlspecialchars($result['flag'] ?? '');

            $html .= '<tr><td>' . $paramName . '</td><td>' . $resultValue . '</td><td>' . $unit . '</td><td>' . $flag . '</td></tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div class="footer">Generated on ' . $createdAt . '</div>';
        $html .= '</body></html>';

        return $html;
    }
}
