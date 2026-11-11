<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\LaboratoryRequest;
use App\Models\TestResult;
use App\Models\Payment;
use App\Models\LaboratoryRequestTest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = Carbon::today();

        // Get statistics
        $stats = [
            'totalPatients' => Patient::count(),
            'pendingRequests' => LaboratoryRequest::whereHas('tests', function ($query) {
                $query->where('item_status', 'Pending');
            })->count(),
            'todaysTests' => TestResult::whereDate('created_at', $today)->count(),
            'todaysRevenue' => Payment::whereDate('created_at', $today)->sum('amount'),
        ];

        // Get recent laboratory requests
        $recentRequests = LaboratoryRequest::with(['patient'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($request) {
                return [
                    'request_number' => $request->request_number,
                    'patient' => $request->patient->name,
                    'status' => $request->status,
                    'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                ];
            });

        // Get recent test results
        $recentResults = TestResult::with(['test', 'laboratoryRequest.patient'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($result) {
                return [
                    'test' => $result->test->name,
                    'patient' => $result->laboratoryRequest->patient->name,
                    'result' => $result->result,
                    'created_at' => $result->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'stats' => $stats,
            'recentRequests' => $recentRequests,
            'recentResults' => $recentResults,
        ]);
    }

    /**
     * Get statistics specifically for the Front Desk view.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFrontDeskStats(): JsonResponse
    {
        $today = Carbon::today();

        $pendingCount = LaboratoryRequest::whereHas('tests', function ($query) {
            $query->where('item_status', 'Pending');
        })->count();

        $inProgressCount = LaboratoryRequest::whereHas('tests', function ($query) {
            $query->whereIn('item_status', ['Collected', 'In Progress', 'For Re-run']);
        })->count();

        $completedTodayCount = LaboratoryRequest::whereDate('updated_at', $today)
            ->whereDoesntHave('tests', function ($query) {
                $query->whereNotIn('item_status', ['Validated', 'Printed']);
            })
            ->count();
        $totalPatientsCount = Patient::count();

        return response()->json([
            'pending' => $pendingCount,
            'in_progress' => $inProgressCount,
            'completed_today' => $completedTodayCount,
            'total_patients' => $totalPatientsCount,
        ]);
    }

    /**
     * Stats for the Phlebotomy / Order pipeline.
     * Mirrors OrderManagement/Phlebotomy getPhlebotomyStats so the
     * /dashboard/phlebotomy-stats route (registered in api.php) resolves.
     */
    public function getPhlebotomyStats(): JsonResponse
    {
        $pendingCollectionCount = LaboratoryRequestTest::where('item_status', 'Pending')->count();

        $collectedTodayCount = LaboratoryRequestTest::where('item_status', 'Collected')
            ->whereDate('collected_at', Carbon::today())
            ->count();

        $acceptedCount = LaboratoryRequestTest::whereIn('item_status', ['Accepted', 'Checked-in'])->count();
        $sendOutCount = LaboratoryRequestTest::where('item_status', 'Send-out')->count();
        $parkedCount = LaboratoryRequestTest::where('item_status', 'Parked')->count();

        return response()->json([
            'pending_collections' => $pendingCollectionCount,
            'collected_today' => $collectedTodayCount,
            'accepted' => $acceptedCount,
            'send_out' => $sendOutCount,
            'parked' => $parkedCount,
        ]);
    }
}