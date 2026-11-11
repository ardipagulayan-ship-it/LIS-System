<?php

namespace App\Services;

use App\Models\LaboratoryRequest;
use App\Models\TestResult;
use Illuminate\Support\Facades\Log;

/**
 * Basic CDS rule engine that evaluates nested AND/OR logic expressed as JSON.
 *
 * JSON structure example (conceptual):
 * {
 *   "op": "OR",
 *   "conditions": [
 *     {"parameter_id": 123, "operator": "<", "value": 50},
 *     {
 *       "op": "AND",
 *       "conditions": [
 *         {"parameter_id": 456, "operator": ">=", "value": 1.2, "metric": "ratio_vs_previous", "source": "absolute"},
 *         {"parameter_id": 789, "operator": "<", "value": 4.0}
 *       ]
 *     }
 *   ]
 * }
 *
 * Supported leaf condition fields:
 * - parameter_id (required)
 * - operator: one of <, <=, >, >=, ==, !=
 * - value: numeric
 * - metric (optional):
 *   - "delta_percent" => compares percent drop from previous: (prev - current)/prev
 *   - "delta_absolute" => compares |current - prev|
 *   - "ratio_vs_previous" => compares current/prev
 */
class CdsRuleEngine
{
    /**
     * Evaluate a rule JSON against patient data from a laboratory request.
     *
     * @param array $ruleJson Parsed JSON array
     * @param LaboratoryRequest $request
     * @param array<int> $neededParameterIds List of parameter IDs to prefetch
     * @return bool True if rule fires
     */
    public function evaluate(array $ruleJson, LaboratoryRequest $request, array $neededParameterIds = []): bool
    {
        // Build a cache of current and previous values for referenced parameters
        $data = $this->assembleData($request, $neededParameterIds);
        return $this->evalNode($ruleJson, $data);
    }

    /**
     * Assemble current and previous values for referenced parameters.
     * @return array [paramId => ['current' => float|null, 'previous' => float|null]]
     */
    protected function assembleData(LaboratoryRequest $request, array $parameterIds): array
    {
        $result = [];
        if (empty($parameterIds)) {
            return $result;
        }

        // Current values from this request
        $current = TestResult::where('laboratory_request_id', $request->id)
            ->whereIn('test_parameter_id', $parameterIds)
            ->get(['test_parameter_id', 'result_value'])
            ->groupBy('test_parameter_id');

        // Previous values from the same patient (most recent before this request)
        $patientId = $request->patient_id;
        $previous = TestResult::whereHas('laboratoryRequest', function ($q) use ($patientId, $request) {
                $q->where('patient_id', $patientId)
                  ->where('id', '<>', $request->id)
                  ->where('request_date', '<=', $request->request_date ?? now());
            })
            ->whereIn('test_parameter_id', $parameterIds)
            ->orderByDesc('created_at')
            ->get(['test_parameter_id', 'result_value'])
            ->groupBy('test_parameter_id');

        foreach ($parameterIds as $pid) {
            $result[$pid] = [
                'current' => optional($current->get($pid)?->first())->result_value,
                'previous' => optional($previous->get($pid)?->first())->result_value,
            ];
        }

        return $result;
    }

    protected function evalNode($node, array $data): bool
    {
        if (!is_array($node)) {
            return false;
        }

        if (isset($node['op'])) {
            $op = strtoupper($node['op']);
            $conditions = $node['conditions'] ?? [];
            if ($op === 'AND') {
                foreach ($conditions as $child) {
                    if (!$this->evalNode($child, $data)) {
                        return false;
                    }
                }
                return true;
            }
            if ($op === 'OR') {
                foreach ($conditions as $child) {
                    if ($this->evalNode($child, $data)) {
                        return true;
                    }
                }
                return false;
            }
            return false;
        }

        // Leaf condition
        $parameterId = $node['parameter_id'] ?? null;
        $operator = $node['operator'] ?? null;
        $value = $node['value'] ?? null;
        if (!$parameterId || $operator === null) {
            return false;
        }

        $curr = isset($data[$parameterId]['current']) ? (float) $data[$parameterId]['current'] : null;
        $prev = isset($data[$parameterId]['previous']) ? (float) $data[$parameterId]['previous'] : null;

        $metric = $node['metric'] ?? 'absolute';

        $lhs = null;
        if ($metric === 'absolute') {
            $lhs = $curr;
        } elseif ($metric === 'delta_percent') {
            if ($prev === null || $prev == 0 || $curr === null) return false;
            $lhs = ($prev - $curr) / $prev; // fraction drop
        } elseif ($metric === 'delta_absolute') {
            if ($prev === null || $curr === null) return false;
            $lhs = abs($curr - $prev);
        } elseif ($metric === 'ratio_vs_previous') {
            if ($prev === null || $prev == 0 || $curr === null) return false;
            $lhs = $curr / $prev;
        } else {
            return false;
        }

        if ($lhs === null) return false;

        return $this->compare($lhs, $operator, (float)$value);
    }

    protected function compare(float $lhs, string $operator, float $rhs): bool
    {
        return match ($operator) {
            '<' => $lhs < $rhs,
            '<=' => $lhs <= $rhs,
            '>' => $lhs > $rhs,
            '>=' => $lhs >= $rhs,
            '==' => $lhs == $rhs,
            '!=' => $lhs != $rhs,
            default => false,
        };
    }
}



