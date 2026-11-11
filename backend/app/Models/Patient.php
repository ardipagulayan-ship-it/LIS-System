<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes; // If using soft deletes

class Patient extends Model
{
    use HasFactory, SoftDeletes; // Add SoftDeletes if used in migration

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'email',
        'phone',
        'gender',
        'date_of_birth',
        'address',
        'civil_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Get the laboratory requests for the patient.
     */
    public function laboratoryRequests(): HasMany
    {
        return $this->hasMany(LaboratoryRequest::class);
    }

    /**
     * Get the patient type associated with the patient.
     */
    public function patientType()
    {
        // Assuming 'patient_type_id' is the foreign key on the 'patients' table
        // and it references the 'id' on the 'patient_types' table.
        return $this->belongsTo(PatientType::class);
    }

    public function deltaChecks(): HasMany
    {
        return $this->hasMany(DeltaCheck::class);
    }
}
