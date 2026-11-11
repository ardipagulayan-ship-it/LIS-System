<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    // Add relationships here if needed
}
