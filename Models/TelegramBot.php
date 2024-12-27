<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TelegramBot extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'username',
        'webhook_url',
        'secret_token',
        'is_active'
    ];

    protected $hidden = [
        'token',
        'secret_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($bot) {
            $bot->secret_token = Str::random(32);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 