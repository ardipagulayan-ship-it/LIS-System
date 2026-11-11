<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\QualityControlLockoutService;

class QualityControlLockoutController extends Controller
{
    protected QualityControlLockoutService $lockoutService;

    public function __construct()
    {
        $this->lockoutService = new QualityControlLockoutService();
    }

    /**
     * GET /qc-lockout/status-summary?test_category_id=ID
     * Returns per-analyzer QC hold summary for a test category.
     */
    public function statusSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_category_id' => 'required|integer|exists:test_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $summary = $this->lockoutService->summarizeEquipmentForCategory((int) $request->input('test_category_id'));
        return response()->json($summary);
    }

    /**
     * GET /qc-lockout/locked-results/{equipmentId}
     */
    public function lockedResultsByEquipment(int $equipmentId)
    {
        $results = $this->lockoutService->getLockedResultsByEquipment($equipmentId);
        return response()->json($results);
    }

    /**
     * POST /qc-lockout/release
     * Body: { equipment_id, test_parameter_id, corrective_action_id? }
     */
    public function release(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'equipment_id' => 'required|integer|exists:equipment,id',
            'test_parameter_id' => 'required|integer|exists:test_parameters,id',
            'corrective_action_id' => 'nullable|integer|exists:corrective_actions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ok = $this->lockoutService->releaseLockout(
            (int) $request->input('equipment_id'),
            (int) $request->input('test_parameter_id'),
            $request->input('corrective_action_id') ? (int) $request->input('corrective_action_id') : null
        );

        if (!$ok) {
            return response()->json([
                'message' => 'QC still out of control. Cannot release lockout.',
            ], 409);
        }

        return response()->json(['success' => true]);
    }
}



