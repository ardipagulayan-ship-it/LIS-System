<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    /**
     * Get the roles for the user.
     */
    public function roles()
    {
        return $this->hasMany(UserRole::class);
    }
    
    /**
     * Check if user has a specific role.
     */
    public function hasRole($roleType)
    {
        return $this->roles()->where('role_type', $roleType)
                   ->where('is_active', true)
                   ->exists();
    }
    
    /**
     * Assign a role to the user.
     */
    public function assignRole($roleType)
    {
        if (!$this->hasRole($roleType)) {
            return $this->roles()->create([
                'role_type' => $roleType,
                'is_active' => true
            ]);
        }
        
        return null;
    }
}
