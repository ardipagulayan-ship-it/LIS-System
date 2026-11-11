<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // For gender validation

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Basic pagination and search
        $query = Patient::query();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('last_name', 'like', $searchTerm)
                  ->orWhere('first_name', 'like', $searchTerm)
                  ->orWhere('middle_name', 'like', $searchTerm)   
                  ->orWhere('id', 'like', $searchTerm); // Allow searching by ID
            });
        }

        // Consider adding sorting options: ->orderBy(...)

        $patients = $query->paginate($request->input('per_page', 10));

        return response()->json($patients);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:patients,email',
            'phone' => 'nullable|string|max:50',
            'gender' => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
            'date_of_birth' => 'nullable|date_format:Y-m-d', // Expect YYYY-MM-DD from frontend
            'address' => 'nullable|string|max:500',
            'civil_status' => 'nullable|string|max:50',
        ]);

        // Convert date format if necessary before creating
        // if(isset($validated['date_of_birth'])) {
        //    $validated['date_of_birth'] = \Carbon\Carbon::parse($validated['date_of_birth'])->format('Y-m-d');
        // }

        $patient = Patient::create($validated);

        return response()->json($patient, 201); // 201 Created
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient)
    {
        // Eager load related data if needed later
        // $patient->load('laboratoryRequests');
        return response()->json($patient);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Patient $patient)
    {
         $validated = $request->validate([
            'last_name' => 'sometimes|required|string|max:255',
            'first_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'nullable|string|max:255',   
            'email' => ['nullable', 'email', 'max:255', Rule::unique('patients')->ignore($patient->id)],
            'phone' => 'nullable|string|max:50',
            'gender' => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'address' => 'nullable|string|max:500',
            'civil_status' => 'nullable|string|max:50',
        ]);

        // Convert date format if necessary
        // if(isset($validated['date_of_birth'])) {
        //    $validated['date_of_birth'] = \Carbon\Carbon::parse($validated['date_of_birth'])->format('Y-m-d');
        // }

        $patient->update($validated);

        return response()->json($patient);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        // Check if soft deletes are enabled on the model
        $patient->delete(); // Will soft delete if trait is used, otherwise hard delete
        return response()->json(null, 204); // 204 No Content
    }
}