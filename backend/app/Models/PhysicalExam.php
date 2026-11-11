<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'nurse_id',
        'vitals',
        'notes',
        'symptoms',
    ];

    protected $casts = [
        'vitals' => 'array',
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