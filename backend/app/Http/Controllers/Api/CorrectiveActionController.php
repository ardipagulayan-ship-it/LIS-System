<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CorrectiveAction;
use App\Models\QualityControlResult;
use App\Models\QualityControlTestParameter;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CorrectiveActionController extends Controller
{
    /**
     * Store a corrective action for a rejected QC result.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quality_control_result_id' => 'required|exists:quality_control_results,id',
            'quality_control_test_parameter_id' => 'required|exists:quality_control_test_parameter,id',
            'rule_violated' => 'required|string',
            'suspected_error' => 'required|string',
            'investigation' => 'required|string',
            'resolution' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $qcResult = QualityControlResult::findOrFail($request->quality_control_result_id);

            // Create the corrective action
            $correctiveAction = CorrectiveAction::create([
                'quality_control_result_id' => $request->quality_control_result_id,
                'quality_control_test_parameter_id' => $request->quality_control_test_parameter_id,
                'rule_violated' => $request->rule_violated,
                'suspected_error' => $request->suspected_error,
                'investigation' => $request->investigation,
                'resolution' => $request->resolution,
                'investigated_by' => Auth::id(),
                'investigation_date' => now(),
                'issue_resolved' => !empty($request->resolution),
                'resolution_date' => !empty($request->resolution) ? now() : null,
                'resolved_by' => !empty($request->resolution) ? Auth::id() : null,
            ]);

            // Mark issue as resolved if resolution is provided
            if (!empty($request->resolution)) {
                $correctiveAction->update([
                    'issue_resolved' => true,
                    'resolution_date' => now(),
                    'resolved_by' => Auth::id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Corrective action logged successfully',
                'data' => $correctiveAction->load(['investigator', 'resolver', 'qualityControlResult']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error logging corrective action: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a corrective action with resolution details.
     */
    public function update(Request $request, CorrectiveAction $correctiveAction)
    {
        $validator = Validator::make($request->all(), [
            'resolution' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $correctiveAction->update([
            'resolution' => $request->resolution,
            'issue_resolved' => true,
            'resolution_date' => now(),
            'resolved_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Corrective action updated successfully',
            'data' => $correctiveAction->load(['investigator', 'resolver', 'qualityControlResult']),
        ]);
    }

    /**
     * Get all corrective actions for a specific QC test parameter.
     */
    public function getByQCTestParameter(QualityControlTestParameter $qualityControlTestParameter)
    {
        $actions = CorrectiveAction::where('quality_control_test_parameter_id', $qualityControlTestParameter->id)
            ->with(['investigator', 'resolver', 'qualityControlResult'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($actions);
    }
}
