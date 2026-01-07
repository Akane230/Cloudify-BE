<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'conversation_type',
        'name',
        'description',
        'avatar_url',
        'created_by',
    ];

    protected $cast = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function created_by(){
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
