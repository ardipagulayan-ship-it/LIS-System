<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'test_category_id',
        'reference_range',
        'unit',
        'turnaround_time_minutes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the category that the test belongs to.
     */
    public function testCategory(): BelongsTo
    {
        // Assumes foreign key is 'test_category_id'
        return $this->belongsTo(TestCategory::class, 'test_category_id');
    }

        /**
     * Get the category that the test belongs to.
     */
    public function category(): BelongsTo
    {
        // Assumes foreign key is 'test_category_id'
        return $this->belongsTo(TestCategory::class, 'test_category_id');
    }

    /**
     * The laboratory requests that include this test.
     */
    public function laboratoryRequests(): BelongsToMany
    {
        return $this->belongsToMany(LaboratoryRequest::class, 'laboratory_request_test')
                    ->withPivot('id', 'item_status', 'price_at_request') // Include pivot data if needed when accessing through Test
                    ->withTimestamps();
    }

    /**
     * Get the parameters associated with the test.
     */
    public function testParameters(): HasMany
    {
        return $this->hasMany(TestParameter::class);
    }

    public function deltaChecks(): HasMany
    {
        return $this->hasMany(DeltaCheck::class);
    }

    /**
     * Get the packages that include this test.
     * This relationship allows the reflex test service to expand packages to all included tests.
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(TestPackage::class, 'package_test')
                    ->withTimestamps();
    }
}
