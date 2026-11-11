<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'laboratory_request_id',
        'amount',
        'payment_method',
        'reference_number',
        'payment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function laboratoryRequest()
    {
        return $this->belongsTo(LaboratoryRequest::class);
    }
}
