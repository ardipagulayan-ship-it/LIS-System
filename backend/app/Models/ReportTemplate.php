<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $table = 'report_templates';

    protected $fillable = [
        'name',
        'code',
        'schema_data',
        'page_settings',
        'is_active',
    ];

    protected $casts = [
        'schema_data' => 'array',
        'page_settings' => 'array',
        'is_active' => 'boolean',
    ];
}
