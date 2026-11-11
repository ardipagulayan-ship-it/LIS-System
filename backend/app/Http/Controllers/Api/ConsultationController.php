<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\MedicalHistory;
use App\Models\PhysicalExam;
use App\Models\Diagnosis;
use App\Models\Prescription;
use App\Models\ConsultationLabRequest;
use App\Models\LaboratoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConsultationController extends Controller
{
    // List all consultations
    public function index(Request $request)
    {
        $consultations = Consultation::with(['patient', 'referringPhysician', 'creator'])->latest()->paginate(20);
        return response()->json($consultations);
    }

    // Create a new consultation
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'reason' => 'required|string',
            'urgency' => 'required|string',
            'referring_physician_id' => 'nullable|exists:doctors,id',
        ]);
        $consultation = Consultation::create([
            'consultation_id' => 'CONS-' . strtoupper(Str::random(8)),
            'patient_id' => $validated['patient_id'],
            'reason' => $validated['reason'],
            'urgency' => $validated['urgency'],
            'referring_physician_id' => $validated['referring_physician_id'] ?? null,
            'created_by' => Auth::id() ?? 1,
            'status' => 'pending',
        ]);
        return response()->json($consultation, 201);
    }

    // Show a single consultation
    public function show($id)
    {
        $consultation = Consultation::with(['patient', 'referringPhysician', 'creator'])->findOrFail($id);
        return response()->json($consultation);
    }

    // Update a consultation
    public function update(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'reason' => 'sometimes|required|string',
            'urgency' => 'sometimes|required|string',
            'referring_physician_id' => 'nullable|exists:doctors,id',
            'status' => 'sometimes|required|string',
        ]);
        $consultation->update($validated);
        return response()->json($consultation);
    }

    // Delete a consultation
    public function destroy($id)
    {
        $consultation = Consultation::findOrFail($id);
        $consultation->delete();
        return response()->json(null, 204);
    }

    // Add medical history (nurse)
    public function addMedicalHistory(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'chief_complaint' => 'nullable|string',
            'history_of_present_illness' => 'nullable|string',
            'past_medical_history' => 'nullable|string',
            'other_medical_conditions' => 'nullable|string',
            'allergies' => 'nullable|string',
            'adverse_drug_reaction' => 'nullable|string',
            'blood_transfusion' => 'nullable|string',
            'radiation' => 'nullable|string',
            'previous_hospitalization' => 'nullable|string',
            'previous_operations' => 'nullable|boolean',
            'previous_operations_details' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'drugs' => 'nullable|boolean',
            'alcohol' => 'nullable|boolean',
            'smoking' => 'nullable|boolean',
            'social_others' => 'nullable|string',
        ]);
        $history = MedicalHistory::create(array_merge([
            'consultation_id' => $consultation->id,
            'nurse_id' => Auth::id(),
        ], $validated));
        return response()->json($history, 201);
    }

    // Add physical exam (nurse)
    public function addPhysicalExam(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'vitals' => 'nullable|array',
            'notes' => 'nullable|string',
            'symptoms' => 'nullable|string',
        ]);
        $exam = PhysicalExam::create([
            'consultation_id' => $consultation->id,
            'nurse_id' => '1',
            'vitals' => $validated['vitals'] ?? [],
            'notes' => $validated['notes'] ?? null,
            'symptoms' => $validated['symptoms'] ?? null,
        ]);
        return response()->json($exam, 201);
    }

    // Add diagnosis (doctor)
    public function addDiagnosis(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'diagnosis' => 'required|string',
            'clinical_notes' => 'nullable|string',
        ]);
        $diagnosis = Diagnosis::create([
            'consultation_id' => $consultation->id,
            'doctor_id' => '1',
            'diagnosis' => $validated['diagnosis'],
            'clinical_notes' => $validated['clinical_notes'] ?? null,
        ]);
        return response()->json($diagnosis, 201);
    }

    // Add prescription (doctor)
    public function addPrescription(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'prescription' => 'required|string',
        ]);
        $prescription = Prescription::create([
            'consultation_id' => $consultation->id,
            'doctor_id' => '1',
            'prescription' => $validated['prescription'],
        ]);
        return response()->json($prescription, 201);
    }

    // Create a lab request from a consultation (doctor)
    public function createLabRequest(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'test_items' => 'required|array|min:1',
            'test_items.*' => 'required|integer|exists:tests,id',
        ]);
        // Create a new LaboratoryRequest (reuse existing logic if possible)
        $labRequest = LaboratoryRequest::create([
            'patient_id' => $consultation->patient_id,
            'doctor_id' => '1',
            'priority_level' => 'Routine',
            'request_date' => now(),
            'created_by' => '1',
        ]);
        $labRequest->tests()->attach(array_map(function($testId) {
            return [ 'test_id' => $testId, 'item_status' => 'Pending' ];
        }, $validated['test_items']));
        // Link to consultation
        ConsultationLabRequest::create([
            'consultation_id' => $consultation->id,
            'laboratory_request_id' => $labRequest->id,
        ]);
        return response()->json($labRequest, 201);
    }

    // Family History
    public function addFamilyHistory(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'history' => 'nullable|string',
            'father' => 'nullable|string',
            'mother' => 'nullable|string',
            'others' => 'nullable|string',
            'specifics' => 'nullable|string',
        ]);
        $record = $consultation->familyHistories()->create($validated);
        return response()->json($record, 201);
    }

    // Maternal History
    public function addMaternalHistory(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'history' => 'nullable|string',
            'mother' => 'nullable|string',
            'others' => 'nullable|string',
            'specifics' => 'nullable|string',
        ]);
        $record = $consultation->maternalHistories()->create($validated);
        return response()->json($record, 201);
    }

    // Birth History
    public function addBirthHistory(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'history' => 'nullable|string',
            'others' => 'nullable|string',
            'specifics' => 'nullable|string',
        ]);
        $record = $consultation->birthHistories()->create($validated);
        return response()->json($record, 201);
    }

    // Diagnostics
    public function addDiagnostics(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'chest_xray' => 'nullable|string',
            'urinalysis' => 'nullable|string',
            'cbc' => 'nullable|string',
            'ecg' => 'nullable|string',
            'stool_exam' => 'nullable|string',
            'drug_test' => 'nullable|string',
            'hbsag' => 'nullable|string',
            'chemistry' => 'nullable|string',
            'blood_type' => 'nullable|string',
            'others' => 'nullable|string',
        ]);
        $record = $consultation->diagnostics()->create($validated);
        return response()->json($record, 201);
    }

    // Assessment
    public function addAssessment(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'fitness_class' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);
        $record = $consultation->assessments()->create($validated);
        return response()->json($record, 201);
    }

    // Medical Certificate
    public function addMedicalCertificate(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'certificate_type' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'reason_of_consultation' => 'nullable|string',
            'examined_on' => 'nullable|string',
            'recovery_duration' => 'nullable|string',
            'physically_fit_for' => 'nullable|string',
            'physically_fit_for_others' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);
        $record = $consultation->medicalCertificates()->create($validated);
        return response()->json($record, 201);
    }

    // Doctor Note
    public function addDoctorNote(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $validated = $request->validate([
            'note' => 'required|string',
            'note_date' => 'required|date',
        ]);
        $record = $consultation->doctorNotes()->create($validated);
        return response()->json($record, 201);
    }
} 