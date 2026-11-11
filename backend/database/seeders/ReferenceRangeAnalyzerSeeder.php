<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReferenceRange;
use App\Models\TestParameter;
use App\Models\Analyzer;
use Illuminate\Support\Facades\DB;

class ReferenceRangeAnalyzerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get analyzers (especially the hematology ones)
        $sysmex = Analyzer::where('name', 'Sysmex XN-550')->first();
        $mindray = Analyzer::where('name', 'Mindray BC-6200')->first();
        $manual = Analyzer::where('name', 'Manual')->first();
        
        if (!$sysmex || !$mindray || !$manual) {
            $this->command->error('Required analyzers not found. Please run AnalyzerSeeder first.');
            return;
        }
        
        // Find hematology test parameters like CBC
        $parameters = TestParameter::whereHas('test', function($query) {
            $query->whereHas('testCategory', function($q) {
                $q->where('name', 'Hematology');
            });
        })->get();
        
        if ($parameters->isEmpty()) {
            $this->command->error('No hematology test parameters found.');
            return;
        }
        
        $this->command->info('Found ' . $parameters->count() . ' hematology parameters.');
        
        // Get the actual column names from the reference_ranges table
        $columns = DB::getSchemaBuilder()->getColumnListing('reference_ranges');
        $this->command->info('Available columns in reference_ranges table: ' . implode(', ', $columns));
        
        // Keep track of created ranges
        $createdRanges = 0;
        
        // For each parameter, create analyzer-specific reference ranges
        foreach ($parameters as $parameter) {
            $this->command->info("Creating reference ranges for parameter: {$parameter->test_name}");
            
            // Get parameter-specific ranges based on parameter name
            $ranges = $this->getParameterRanges($parameter->test_name);
            
            // Base data array with common fields
            $baseData = [
                'test_parameter_id' => $parameter->id,
                'equipment_id' => null,
                'si_range' => null,
                'si_unit' => $parameter->unit ?: '',
                'conv_range' => null,
                'conv_unit' => $parameter->conventional_unit ?: '',
                'low_critical_value' => null,
                'low_value' => null,
                'high_value' => null,
                'high_critical_value' => null,
                'gender' => 'Any',
                // Add the age fields matching database column names
                'age_min' => 0,
                'age_max' => 120,
                'age_unit' => 'Years'
            ];
            
            // Sysmex analyzer reference ranges
            $sysmexData = array_merge($baseData, [
                'equipment_id' => $sysmex->id,
                'low_value' => $ranges['sysmex']['low'],
                'high_value' => $ranges['sysmex']['high'],
                'low_critical_value' => $ranges['sysmex']['critical_low'],
                'high_critical_value' => $ranges['sysmex']['critical_high'],
                'si_range' => $ranges['sysmex']['low'] . ' - ' . $ranges['sysmex']['high'],
                'conv_range' => $ranges['sysmex']['low'] . ' - ' . $ranges['sysmex']['high'],
                'age_min' => 18,
                'age_max' => 99,
                'age_unit' => 'Years'
            ]);
            
            ReferenceRange::create($sysmexData);
            $createdRanges++;
            
            // Mindray analyzer reference ranges
            $mindrayData = array_merge($baseData, [
                'equipment_id' => $mindray->id,
                'low_value' => $ranges['mindray']['low'],
                'high_value' => $ranges['mindray']['high'],
                'low_critical_value' => $ranges['mindray']['critical_low'],
                'high_critical_value' => $ranges['mindray']['critical_high'],
                'si_range' => $ranges['mindray']['low'] . ' - ' . $ranges['mindray']['high'],
                'conv_range' => $ranges['mindray']['low'] . ' - ' . $ranges['mindray']['high'],
                'age_min' => 18,
                'age_max' => 99,
                'age_unit' => 'Years'
            ]);
            
            ReferenceRange::create($mindrayData);
            $createdRanges++;
            
            // Manual testing reference ranges
            $manualData = array_merge($baseData, [
                'equipment_id' => $manual->id,
                'low_value' => $ranges['manual']['low'],
                'high_value' => $ranges['manual']['high'],
                'low_critical_value' => $ranges['manual']['critical_low'],
                'high_critical_value' => $ranges['manual']['critical_high'],
                'si_range' => $ranges['manual']['low'] . ' - ' . $ranges['manual']['high'],
                'conv_range' => $ranges['manual']['low'] . ' - ' . $ranges['manual']['high'],
                'age_min' => 18,
                'age_max' => 99,
                'age_unit' => 'Years'
            ]);
            
            ReferenceRange::create($manualData);
            $createdRanges++;
        }
        
        $this->command->info("Created {$createdRanges} analyzer-specific reference ranges.");
    }
    
    /**
     * Get parameter-specific reference ranges based on parameter name
     *
     * @param string $parameterName
     * @return array
     */
    private function getParameterRanges($parameterName)
    {
        // Default ranges
        $default = [
            'sysmex' => [
                'low' => 4.0, 'high' => 11.0, 'critical_low' => 2.5, 'critical_high' => 15.0
            ],
            'mindray' => [
                'low' => 4.0, 'high' => 11.0, 'critical_low' => 2.5, 'critical_high' => 15.5
            ],
            'manual' => [
                'low' => 4.5, 'high' => 9.5, 'critical_low' => 3.0, 'critical_high' => 14.0
            ]
        ];
        
        // Parameter-specific ranges
        switch (strtolower(trim($parameterName))) {
            case 'white blood cell count':
                return [
                    'sysmex' => [
                        'low' => 4.0, 'high' => 11.0, 'critical_low' => 2.5, 'critical_high' => 15.0
                    ],
                    'mindray' => [
                        'low' => 4.0, 'high' => 11.0, 'critical_low' => 2.5, 'critical_high' => 15.5
                    ],
                    'manual' => [
                        'low' => 4.5, 'high' => 9.5, 'critical_low' => 3.0, 'critical_high' => 14.0
                    ]
                ];
                
            case 'red blood cell count':
                return [
                    'sysmex' => [
                        'low' => 4.5, 'high' => 5.5, 'critical_low' => 3.5, 'critical_high' => 6.5
                    ],
                    'mindray' => [
                        'low' => 4.0, 'high' => 5.3, 'critical_low' => 3.0, 'critical_high' => 6.0
                    ],
                    'manual' => [
                        'low' => 4.2, 'high' => 5.4, 'critical_low' => 3.2, 'critical_high' => 6.2
                    ]
                ];
                
            case 'hemoglobin':
                return [
                    'sysmex' => [
                        'low' => 12.0, 'high' => 16.0, 'critical_low' => 9.0, 'critical_high' => 18.0
                    ],
                    'mindray' => [
                        'low' => 12.0, 'high' => 16.0, 'critical_low' => 8.0, 'critical_high' => 20.0
                    ],
                    'manual' => [
                        'low' => 12.5, 'high' => 16.5, 'critical_low' => 9.5, 'critical_high' => 19.0
                    ]
                ];
                
            case 'hematocrit':
                return [
                    'sysmex' => [
                        'low' => 37.0, 'high' => 47.0, 'critical_low' => 27.0, 'critical_high' => 55.0
                    ],
                    'mindray' => [
                        'low' => 38.0, 'high' => 48.0, 'critical_low' => 28.0, 'critical_high' => 57.0
                    ],
                    'manual' => [
                        'low' => 36.0, 'high' => 46.0, 'critical_low' => 26.0, 'critical_high' => 54.0
                    ]
                ];
                
            case 'platelet count':
                return [
                    'sysmex' => [
                        'low' => 150.0, 'high' => 450.0, 'critical_low' => 100.0, 'critical_high' => 600.0
                    ],
                    'mindray' => [
                        'low' => 150.0, 'high' => 450.0, 'critical_low' => 100.0, 'critical_high' => 600.0
                    ],
                    'manual' => [
                        'low' => 150.0, 'high' => 450.0, 'critical_low' => 100.0, 'critical_high' => 600.0
                    ]
                ];
                
            default:
                return $default;
        }
    }
}
