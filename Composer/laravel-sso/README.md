# Laravel SSO connector

## Installation

To include the SSO connector within the project, update your `composer.json` file accordingly:

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": [
        {
            "type": "path",
            "url": "../Composer/laravel-sso"
        }
    ],
    "require": {
        // ... 
        "remp/laravel-sso": "*"
    }
}
```

Include the service providers within your `config/app.php`:

```php
'providers' => [
    // ...
    Remp\LaravelSso\Providers\SsoServiceProvider::class,
    // ...
];
```

### Web authentication (JWT)

Add new middleware to `$routeMiddleware` within your `App\Http\Kernel`:

```php
protected $routeMiddleware = [
    // ...
    'auth.jwt' => VerifyJwtToken::class,
    // ...
];
```

Switch your application authentication in `config/auth.php`:

```php
'defaults' => [
    'guard' => 'jwt',
    'passwords' => null,
],

'guards' => [
    // ...
    'jwt' => [
        'driver' => 'jwt',
        'provider' => null,
    ],
    // ...
]
```

Now you can protect your routes in `routes/web.php` by using `auth.jwt` middleware:

```php
Route::middleware('auth.jwt')->group(function () {
    // ...
    Route::get('ping', 'SystemController@ping')->name('ping');
    Route::resource('foo', 'FooController');
    Route::resource('bar', 'BarController');
    // ...
});
```

#### Accessing user

You can use `Auth` facade to verify user presence and access his data.

```php
Auth::user() // returns instance of Remp\LaravelSso\Contracts\Jwt\User
Auth::id() // returns current user ID
Auth::check() // checks if user is logged in
```

### API authentication (token)

When registered, `SsoServiceProvider` overrides default `token` auth and uses its own guard
to authenticate the caller (`Remp\LaravelSso\Contracts\Token\Guard`).

Auth configuration for API should be then set as follows:

```php
'guards' => [
    // ...
    'api' => [
        'driver' => 'token',
        'provider' => null,
    ],
    // ...
],
```

To make a request, you have to provide valid API token (via `Authorization: Bearer $token`)
generated via REMP SSO web admin. If token is not provided or not valid, middleware will
throw `AuthenticationException` for application's exception handler to handle.

## Configuration

You can configure the connector either via Laravel config or environment variables. Following is the list
of all available configuration options:

| Config | Environment | Default
| --- | --- | --- |
| `services.remp_sso.addr` | `REMP_SSO_ADDR` | `http://sso.remp.press` |
| `services.remp_sso.error_url` | `REMP_SSO_ERROR_URL` | `route('sso.error')` |
