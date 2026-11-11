<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationLabRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'laboratory_request_id',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function laboratoryRequest()
    {
        return $this->belongsTo(LaboratoryRequest::class);
    }
} 