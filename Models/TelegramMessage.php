<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'phone_number',
        'address',
        'area',
        'additional_info',
        'status',
        'registration_step'
    ];

    protected $casts = [
        'area' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id', 'user_id');
    }
}
