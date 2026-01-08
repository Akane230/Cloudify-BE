<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    protected $fillable = [
        'username', 
        'email', 
        'phone_number', 
        'password', 
        'display_name', 
        'profile_picture_url', 
        'bio',
        'status',
        'last_seen',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'last_seen' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'created_by', 'id');
    }
}