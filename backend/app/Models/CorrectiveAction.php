<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectiveAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_control_result_id',
        'quality_control_test_parameter_id',
        'rule_violated',
        'suspected_error',
        'investigation',
        'resolution',
        'investigated_by',
        'investigation_date',
        'issue_resolved',
        'resolution_date',
        'resolved_by',
    ];

    protected $casts = [
        'investigation_date' => 'datetime',
        'issue_resolved' => 'boolean',
        'resolution_date' => 'datetime',
    ];

    /**
     * Get the quality control result that triggered this corrective action.
     */
    public function qualityControlResult(): BelongsTo
    {
        return $this->belongsTo(QualityControlResult::class);
    }

    /**
     * Get the quality control test parameter for this corrective action.
     */
    public function qualityControlTestParameter(): BelongsTo
    {
        return $this->belongsTo(QualityControlTestParameter::class);
    }

    /**
     * Get the user who performed the investigation.
     */
    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigated_by');
    }

    /**
     * Get the user who resolved the issue.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
