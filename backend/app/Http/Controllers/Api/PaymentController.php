<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\LaboratoryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(): JsonResponse
    {
        $payments = Payment::with(['laboratoryRequest.patient'])
            ->latest()
            ->paginate(10);
        return response()->json($payments);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'laboratory_request_id' => 'required|exists:laboratory_requests,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,insurance',
            'transaction_id' => 'nullable|string',
            'insurance_provider' => 'required_if:payment_method,insurance|string',
            'insurance_policy_number' => 'required_if:payment_method,insurance|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Check if the laboratory request has already been paid
            $laboratoryRequest = LaboratoryRequest::findOrFail($request->laboratory_request_id);
            if ($laboratoryRequest->payment) {
                return response()->json([
                    'message' => 'This laboratory request has already been paid'
                ], 422);
            }

            $payment = Payment::create($request->all());
            $payment->load(['laboratoryRequest.patient']);

            DB::commit();
            return response()->json($payment, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating payment'], 500);
        }
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['laboratoryRequest.patient']);
        return response()->json($payment);
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'laboratory_request_id' => 'required|exists:laboratory_requests,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,insurance',
            'transaction_id' => 'nullable|string',
            'insurance_provider' => 'required_if:payment_method,insurance|string',
            'insurance_policy_number' => 'required_if:payment_method,insurance|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Check if the laboratory request has already been paid by another payment
            $laboratoryRequest = LaboratoryRequest::findOrFail($request->laboratory_request_id);
            if ($laboratoryRequest->payment && $laboratoryRequest->payment->id !== $payment->id) {
                return response()->json([
                    'message' => 'This laboratory request has already been paid'
                ], 422);
            }

            $payment->update($request->all());
            $payment->load(['laboratoryRequest.patient']);

            DB::commit();
            return response()->json($payment);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating payment'], 500);
        }
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();
        return response()->json(null, 204);
    }

    public function getUnpaidRequests(): JsonResponse
    {
        $requests = LaboratoryRequest::with(['patient'])
            ->whereDoesntHave('payment')
            ->latest()
            ->get();
        return response()->json($requests);
    }

    public function getPaymentSummary(): JsonResponse
    {
        $summary = [
            'total_payments' => Payment::count(),
            'total_amount' => Payment::sum('amount'),
            'payment_methods' => [
                'cash' => Payment::where('payment_method', 'cash')->sum('amount'),
                'card' => Payment::where('payment_method', 'card')->sum('amount'),
                'insurance' => Payment::where('payment_method', 'insurance')->sum('amount'),
            ],
            'unpaid_requests' => LaboratoryRequest::whereDoesntHave('payment')->count(),
            'unpaid_amount' => LaboratoryRequest::whereDoesntHave('payment')->sum('total_amount'),
        ];
        return response()->json($summary);
    }
} 