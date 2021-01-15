<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\GoogleUser
 *
 * @property string|null $avatar
 * @property \Carbon\Carbon|null $created_at
 * @property int $expires_in
 * @property string $google_id
 * @property int $id
 * @property string|null $name
 * @property string|null $nickname
 * @property string|null $refresh_token
 * @property string $token
 * @property \Carbon\Carbon|null $updated_at
 * @property int $user_id
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereExpiresIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereGoogleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoogleUser whereUserId($value)
 * @mixin \Eloquent
 */
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
