<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'patient_id',
        'reason',
        'urgency',
        'referring_physician_id',
        'created_by',
        'status',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function referringPhysician()
    {
        return $this->belongsTo(Doctor::class, 'referring_physician_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function medicalHistories()
    {
        return $this->hasMany(MedicalHistory::class);
    }

    public function physicalExams()
    {
        return $this->hasMany(PhysicalExam::class);
    }

    public function diagnoses()
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function labRequests()
    {
        return $this->hasMany(ConsultationLabRequest::class);
    }

    public function diagnostics()
    {
        return $this->hasMany(Diagnostic::class);
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function medicalCertificates()
    {
        return $this->hasMany(MedicalCertificate::class);
    }
} 