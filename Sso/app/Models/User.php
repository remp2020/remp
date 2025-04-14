<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    const PROVIDER_GOOGLE = 'google';

    const USER_SUBJECT_SESSION_KEY = 'user_token';

    const USER_LAST_LOGOUT_KEY = 'user_logout';

    public string $latestProvider;

    protected $fillable = [
        'name',
        'email',
    ];

    protected $casts = [
        'last_logout_at' => 'datetime',
    ];

    public function googleUser(): HasOne
    {
        return $this->hasOne(GoogleUser::class);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        /** @var GoogleUser $googleUser */
        $googleUser = $this->googleUser;

        $name = match ($this->latestProvider) {
            self::PROVIDER_GOOGLE => $googleUser->name,
            default => null,
        };

        return [
            'provider' => $this->latestProvider,
            'id' => $this->id,
            'name' => $name,
            'email' => $this->email,
            'scopes' => [],
        ];
    }
}
