<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramUser extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'username',
        'is_agreed'
    ];

    protected $casts = [
        'is_agreed' => 'boolean'
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(TelegramMessage::class, 'telegram_user_id', 'user_id');
    }
}
