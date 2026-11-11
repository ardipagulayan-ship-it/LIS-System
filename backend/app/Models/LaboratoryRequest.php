<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaboratoryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'sample_id',
        'ward_id',
        'room_id',
        'referring_doctor',
        'priority_level',
        'patient_type_id',
        'created_by',
        'request_date',
        'is_reflex',
        'reflex_parent_request_id',
        'reflex_triggered_by_rule_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_date' => 'datetime',
        'is_reflex' => 'boolean',
    ];

    /**
     * Get the patient that owns the laboratory request.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor associated with the laboratory request.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * The tests that belong to the laboratory request.
     */
    public function tests(): BelongsToMany
    {
        // Ensure the pivot table name matches your migration ('laboratory_request_test')
        return $this->belongsToMany(Test::class, 'laboratory_request_test')
                    ->using(LaboratoryRequestTest::class) // Use the custom pivot model
                    ->withPivot(
                        'id',
                        'item_status',
                        'price_at_request',
                        'rejection_reason',
                        'collected_at',
                        'accepted_at',
                        'rejected_at',
                        'remarks',
                        'comment',
                        'sendout_at',
                        'verify_at'
                    ) // Include tracking timestamps needed by reports
                    ->withTimestamps(); // If using timestamps on pivot
    }

    public function results()
    {
        return $this->hasMany(TestResult::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function patientType(): BelongsTo
    {
        return $this->belongsTo(PatientType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the parent request if this is a reflex test.
     */
    public function parentRequest(): BelongsTo
    {
        return $this->belongsTo(LaboratoryRequest::class, 'reflex_parent_request_id');
    }

    /**
     * Get the CDS rule that triggered this reflex request.
     */
    public function reflexTriggerRule(): BelongsTo
    {
        return $this->belongsTo(CdsCorrelationRule::class, 'reflex_triggered_by_rule_id');
    }

    /**
     * Get all reflex children of this request.
     */
    public function reflexRequests(): HasMany
    {
        return $this->hasMany(LaboratoryRequest::class, 'reflex_parent_request_id');
    }
}
