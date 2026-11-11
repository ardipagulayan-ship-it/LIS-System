<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Equipment;
use Symfony\Component\HttpFoundation\Response;

class EquipmentStatusCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a test result submission
        if ($request->isMethod('POST') && $request->routeIs('test-results.store')) {
            $analyzerId = $request->input('analyzer_id');
            
            if ($analyzerId) {
                $equipment = Equipment::find($analyzerId);
                
                if ($equipment && !$equipment->canBeUsedForTesting()) {
                    return response()->json([
                        'message' => 'Equipment cannot be used for testing',
                        'errors' => [
                            'analyzer_id' => [
                                'The selected equipment is not available for testing. ' .
                                'Status: ' . $equipment->current_status . 
                                ($equipment->isOverdueForMaintenance() ? ' (Maintenance overdue)' : '')
                            ]
                        ]
                    ], 422);
                }
            }
        }

        // Check if this is a QC result submission
        if ($request->isMethod('POST') && $request->routeIs('quality-control-results.store')) {
            $analyzerId = $request->input('analyzer_id');
            
            if ($analyzerId) {
                $equipment = Equipment::find($analyzerId);
                
                if ($equipment && !$equipment->canBeUsedForTesting()) {
                    return response()->json([
                        'message' => 'Equipment cannot be used for QC testing',
                        'errors' => [
                            'analyzer_id' => [
                                'The selected equipment is not available for QC testing. ' .
                                'Status: ' . $equipment->current_status . 
                                ($equipment->isOverdueForMaintenance() ? ' (Maintenance overdue)' : '')
                            ]
                        ]
                    ], 422);
                }
            }
        }

        return $next($request);
    }
}

