<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'specialization',
        'contact_number',
        'email',
        'address',
    ];

    /**
     * Get the laboratory requests referred by the doctor.
     */
    public function laboratoryRequests(): HasMany
    {
        return $this->hasMany(LaboratoryRequest::class);
    }
}
