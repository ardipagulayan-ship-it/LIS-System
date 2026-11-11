<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class LaboratoryRequestTest extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laboratory_request_test';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'laboratory_request_id',
        'test_id',
        'item_status',
        'price_at_request',
        'rejection_reason',
        'remarks',
        'comment',
        // New tracking fields
        'checkin_by',
        'checkin_at',
        'sendout_by',
        'sendout_at',
        'park_by',
        'park_at',
        'perform_by',
        'perform_at',
        'verify_by',
        'verify_at',
        'pathologist_id',
        // Reflex tracking fields
        'is_reflex_test',
        'reflex_triggered_at',
        'reflex_triggered_by_rule_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'checkin_at' => 'datetime',
        'sendout_at' => 'datetime',
        'park_at' => 'datetime',
        'perform_at' => 'datetime',
        'verify_at' => 'datetime',
        'reflex_triggered_at' => 'datetime',
    ];

    public function laboratoryRequest()
    {
        return $this->belongsTo(LaboratoryRequest::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    // Relationships for user tracking
    public function checkinUser()
    {
        return $this->belongsTo(User::class, 'checkin_by');
    }

    public function sendoutUser()
    {
        return $this->belongsTo(User::class, 'sendout_by');
    }

    public function parkUser()
    {
        return $this->belongsTo(User::class, 'park_by');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'perform_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verify_by');
    }

    public function pathologist()
    {
        return $this->belongsTo(User::class, 'pathologist_id');
    }

    /**
     * Get the CDS rule that triggered this reflex test.
     */
    public function reflexTriggerRule()
    {
        return $this->belongsTo(CdsCorrelationRule::class, 'reflex_triggered_by_rule_id');
    }
}
