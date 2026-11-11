<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    // Add relationships here if needed (e.g., rooms)
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
