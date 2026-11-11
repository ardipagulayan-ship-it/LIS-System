<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Diagnostic extends Model
{
    use HasFactory;
    protected $fillable = [
        'consultation_id',
        'chest_xray',
        'urinalysis',
        'cbc',
        'ecg',
        'stool_exam',
        'drug_test',
        'hbsag',
        'chemistry',
        'blood_type',
        'others',
    ];
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
} 