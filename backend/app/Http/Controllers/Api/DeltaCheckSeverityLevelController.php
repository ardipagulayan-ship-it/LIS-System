<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeltaCheckSeverityLevel;
use Illuminate\Http\Request;

class DeltaCheckSeverityLevelController extends Controller
{
    /**
     * Display a listing of severity levels.
     */
    public function index(Request $request)
    {
        $query = DeltaCheckSeverityLevel::query();

        // Only return active severity levels by default
        if ($request->has('include_inactive') && $request->include_inactive) {
            // Include all
        } else {
            $query->active();
        }

        $severityLevels = $query->orderedByPriority()->get();

        return response()->json($severityLevels);
    }

    /**
     * Display the specified severity level.
     */
    public function show(DeltaCheckSeverityLevel $deltaCheckSeverityLevel)
    {
        return response()->json($deltaCheckSeverityLevel);
    }
}



