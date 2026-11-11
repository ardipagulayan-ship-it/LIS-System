<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TestPackage extends Model
{
    use HasFactory;

    protected $table = 'test_packages';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the tests that belong to the package.
     */
    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(Test::class, 'package_test')
                    ->withTimestamps();
    }
}
