<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientType;
use Illuminate\Http\Request;

class PatientTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(PatientType::orderBy('name')->get(['id', 'name']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PatientType $patientType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PatientType $patientType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PatientType $patientType)
    {
        //
    }
}
