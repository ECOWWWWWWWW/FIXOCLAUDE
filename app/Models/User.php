<?php

namespace App\Models;

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
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'username',
        'password',
        'role',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    /**
     * Get role name
     */
    public function getRoleNameAttribute()
    {
        $roles = [
            1 => 'Homeowner',
            2 => 'Tradie',
            3 => 'Admin'
        ];
        
        return $roles[$this->role] ?? 'Unknown';
    }
    
    /**
     * Check if user is an admin
     */
    public function isAdmin()
    {
        return $this->role === 3;
    }
    
    /**
     * Route notifications for the Firebase channel.
     */
    public function routeNotificationForFirebase()
    {
        // Return the Firebase device token
        // You'll need to store this in your users table
        return $this->firebase_token;
    }
    
    /**
     * Get audit logs where this user was the target
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'target_id');
    }
    
    /**
     * Get audit logs created by this user (as admin)
     */
    public function adminAuditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}