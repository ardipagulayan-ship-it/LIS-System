<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestTemplateMapping extends Model
{
    use HasFactory;

    protected $table = 'test_template_mappings';

    protected $fillable = [
        'test_type_id',
        'report_template_id',
    ];

    public function reportTemplate()
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }
}
