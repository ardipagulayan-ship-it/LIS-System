<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MedicalCertificate extends Model
{
    use HasFactory;
    protected $fillable = [
        'consultation_id',
        'certificate_type',
        'diagnosis',
        'reason_of_consultation',
        'examined_on',
        'recovery_duration',
        'physically_fit_for',
        'physically_fit_for_others',
        'remarks',
    ];
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
} 