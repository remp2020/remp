<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleUser extends Model
{
    protected $fillable = [
        'nickname',
        'name',
        'avatar',
        'google_id',
    ];

    protected $hidden = [
        'token',
        'refresh_token',
        'expires_in',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
