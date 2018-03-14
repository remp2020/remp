<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * App\User
 *
 * @property \Carbon\Carbon|null $created_at
 * @property string $email
 * @property int $id
 * @property string|null $name
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\GoogleUser $googleUser
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable implements JWTSubject
{
    const PROVIDER_GOOGLE = 'google';

    const USER_SUBJECT_SESSION_KEY = 'user_token';

    const USER_LAST_LOGOUT_KEY = 'user_logout';

    public $latestProvider;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
    ];

    protected $dates = [
        'last_logout_at',
    ];

    public function googleUser()
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
        $name = null;
        switch ($this->latestProvider) {
            case self::PROVIDER_GOOGLE:
                $name = $this->googleUser->name;
        }
        return [
            'provider' => $this->latestProvider,
            'id' => $this->id,
            'name' => $name,
            'email' => $this->email,
            'scopes' => [],
        ];
    }
}
