<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuleDecisionAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_type',
        'decision',
        'message',
        'rule_id',
        'policy',
        'laboratory_request_id',
        'test_result_id',
        'test_parameter_id',
        'test_id',
        'user_id',
        'supervisor_id',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function laboratoryRequest()
    {
        return $this->belongsTo(LaboratoryRequest::class, 'laboratory_request_id');
    }
}
