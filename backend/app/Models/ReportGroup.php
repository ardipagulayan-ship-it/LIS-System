<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportGroup extends Model
{
    use HasFactory;

    protected $table = 'report_groups';

    protected $fillable = [
        'name',
        'test_category_id',
        'report_layout_id',
        'sort_order'
    ];

    public function layout()
    {
        return $this->belongsTo(ReportLayout::class, 'report_layout_id');
    }

    public function category()
    {
        return $this->belongsTo(TestCategory::class, 'test_category_id');
    }

    public function parameters()
    {
        return $this->hasMany(TestParameter::class, 'report_group_id');
    }
}
