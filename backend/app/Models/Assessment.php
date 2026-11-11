<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Assessment extends Model
{
    use HasFactory;
    protected $fillable = [
        'consultation_id',
        'fitness_class',
        'remarks',
    ];
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
} 