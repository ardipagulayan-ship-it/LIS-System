<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'nurse_id',
        'chief_complaint',
        'history_of_present_illness',
        'past_medical_history',
        'other_medical_conditions',
        'allergies',
        'adverse_drug_reaction',
        'blood_transfusion',
        'radiation',
        'previous_hospitalization',
        'previous_operations',
        'previous_operations_details',
        'current_medications',
        'drugs',
        'alcohol',
        'smoking',
        'social_others',
        'entry',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }
} 