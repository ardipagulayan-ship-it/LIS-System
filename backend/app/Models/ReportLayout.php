<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportLayout extends Model
{
    use HasFactory;

    protected $table = 'report_layouts';

    protected $fillable = [
        'name',
        'layout_json',
        'page_size',
        'page_config',
        'sections_json'
    ];

    protected $casts = [
        'layout_json' => 'array',
        'page_config' => 'array',
        'sections_json' => 'array'
    ];

    public function groups()
    {
        return $this->hasMany(ReportGroup::class, 'report_layout_id');
    }
}
