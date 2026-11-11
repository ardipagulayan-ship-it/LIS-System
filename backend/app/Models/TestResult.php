<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // For logging issues

class TestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'laboratory_request_id',
        'test_id',
        'test_parameter_id',
        'result_value',
        'flag',
        'unit',
        'equipment_id',
        'result_comment',
        'performer_id',
        'verifier_id',
        'pathologist_id',
        'status',
        'version',
        'parent_result_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'version' => 'integer',
    ];

    public function laboratoryRequest()
    {
        return $this->belongsTo(LaboratoryRequest::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function testParameter()
    {
        return $this->belongsTo(TestParameter::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performer_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }
    
    public function pathologist()
    {
        return $this->belongsTo(User::class, 'pathologist_id');
    }

    public function deltaChecks()
    {
        return $this->hasMany(DeltaCheck::class, 'test_result_id');
    }

    /**
     * Calculate and set the result flag based on reference ranges.
     * Assumes result is numeric and relationships are loaded or loadable.
     */
    public function calculateFlag(): void
    {
        // Ensure result is numeric and parameter exists
        if (!is_numeric($this->result_value) || !$this->testParameter || !$this->laboratoryRequest?->patient)
        {
            $this->flag = null;
            return;
        }

        $resultValue = (float) $this->result_value;
        $patient = $this->laboratoryRequest->patient;
        $parameter = $this->testParameter;

        // Calculate patient age in years
        $ageInYears = $patient->date_of_birth ? Carbon::parse($patient->date_of_birth)->age : null;
        $gender = $patient->gender ?? 'Any';

        if ($ageInYears === null) {
             $this->flag = null; // Cannot determine range without age
             return;
        }

        // Find the matching reference range considering the equipment
        $query = ReferenceRange::where('test_parameter_id', $parameter->id)
            ->where('age_min', '<=', $ageInYears)
            ->where('age_max', '>=', $ageInYears)
            ->where(function ($q) use ($gender) {
                // Match specific gender or 'Any' gender ranges
                $q->where('gender', $gender)
                  ->orWhere('gender', 'Any');
            });
            
        // If we have an equipment specified, filter by it
        if ($this->equipment_id) {
            $query->where(function ($q) {
                $q->where('equipment_id', $this->equipment_id)
                  ->orWhereNull('equipment_id');
            });
        }
        
        $referenceRange = $query->orderBy('gender', 'desc') // Prioritize specific gender match
                               ->orderByRaw('equipment_id IS NULL') // Prioritize equipment-specific ranges
                               ->first();

        if (!$referenceRange) {
            $this->flag = null; // No matching range found
            return;
        }

        // Compare result to ranges and set flag
        if ($referenceRange->low_critical_value !== null && $resultValue < $referenceRange->low_critical_value) {
            $this->flag = 'L';
        } elseif ($referenceRange->low_value !== null && $resultValue < $referenceRange->low_value) {
            $this->flag = 'l';
        } elseif ($referenceRange->high_critical_value !== null && $resultValue > $referenceRange->high_critical_value) {
            $this->flag = 'H';
        } elseif ($referenceRange->high_value !== null && $resultValue > $referenceRange->high_value) {
            $this->flag = 'h';
        } else {
            $this->flag = null; // Within normal range
        }
    }

    // Placeholder for unit conversion logic
    // private function convertResultToRangeUnits($resultValue, $parameter, $referenceRange)
    // {
    //     // Implement conversion logic here based on parameter/range units and conversion factors
    //     return $resultValue; 
    // }

}
