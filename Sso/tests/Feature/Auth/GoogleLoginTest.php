<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\GoogleController;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google' => [
                'client_id' => 'google-client-id',
                'client_secret' => 'google-client-secret',
                'redirect' => self::SSO_ORIGIN . '/auth/google/callback',
            ],
        ]);
    }

    public function testRedirectStoresOAuthStateInSession(): void
    {
        $response = $this->get($this->authUrl('/auth/google'));

        [$url, $query] = $this->parseRedirect($response);
        $this->assertStringStartsWith('https://accounts.google.com/', $url);
        $this->assertNotEmpty($query['state'] ?? null);
        $response->assertSessionHas('state', $query['state']);
    }

    #[DataProvider('untrustedUrls')]
    public function testRedirectRejectsUntrustedSuccessUrl(string $url): void
    {
        $this->get($this->authUrl('/auth/google', $url))
            ->assertStatus(400)
            ->assertSessionMissing(GoogleController::SUCCESS_URL_KEY);
    }

    #[DataProvider('untrustedUrls')]
    public function testRedirectRejectsUntrustedErrorUrl(string $url): void
    {
        $this->get($this->authUrl('/auth/google', self::SUCCESS_URL, $url))
            ->assertStatus(400)
            ->assertSessionMissing(GoogleController::ERROR_URL_KEY);
    }

    public function testCallbackRejectsUntrustedSuccessUrlFromSession(): void
    {
        $response = $this
            ->withSession([
                GoogleController::SUCCESS_URL_KEY => 'https://attacker.test/',
                GoogleController::ERROR_URL_KEY => self::ERROR_URL,
            ])
            ->get('/auth/google/callback?code=authorization-code');

        $response->assertStatus(400);
        $this->assertFalse($response->headers->has('Location'), 'Rejected request must not redirect anywhere');
    }

    public function testCallbackRejectsUntrustedErrorUrlFromSession(): void
    {
        $response = $this
            ->withSession([
                GoogleController::SUCCESS_URL_KEY => self::SUCCESS_URL,
                GoogleController::ERROR_URL_KEY => 'https://attacker.test/',
            ])
            ->get('/auth/google/callback?code=authorization-code');

        $response->assertStatus(400);
        $this->assertFalse($response->headers->has('Location'), 'Rejected request must not redirect anywhere');
    }

    public function testCallbackWithoutValidStateRedirectsToErrorUrl(): void
    {
        // real provider - the session holds the success/error URL, but no OAuth state
        $response = $this->withSession($this->sessionUrls())->get('/auth/google/callback?code=authorization-code&state=forged');

        [$url, $query] = $this->parseRedirect($response);
        $this->assertSame(self::ERROR_URL, $url);
        $this->assertSame('Login to Google failed, please try again.', $query['error']);
        $this->assertArrayNotHasKey('token', $query);
        $response->assertSessionMissing(User::USER_SUBJECT_SESSION_KEY);
    }

    public function testCallbackWithExpiredSessionRedirectsToSsoErrorPage(): void
    {
        $response = $this->get('/auth/google/callback?code=authorization-code&state=from-expired-session');

        [$url, $query] = $this->parseRedirect($response);
        $this->assertSame(route('sso.error'), $url);
        $this->assertSame(['error' => 'Login session expired, please try again.'], $query);
        $response->assertSessionMissing(User::USER_SUBJECT_SESSION_KEY);
    }

    /**
     * @return array<string, array{\Throwable}>
     */
    public static function providerFailures(): array
    {
        return [
            'StateMismatch_ShouldRedirectToErrorUrl' => [new InvalidStateException()],
            'RejectedAuthorizationCode_ShouldRedirectToErrorUrl' => [new ClientException(
                'invalid_grant',
                new GuzzleRequest('POST', 'https://oauth2.googleapis.com/token'),
                new GuzzleResponse(400),
            )],
        ];
    }

    #[DataProvider('providerFailures')]
    public function testCallbackRedirectsToErrorUrlWhenProviderLoginFails(\Throwable $failure): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andThrow($failure);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->withSession($this->sessionUrls())->get('/auth/google/callback?code=authorization-code');

        [$url, $query] = $this->parseRedirect($response);
        $this->assertSame(self::ERROR_URL, $url);
        $this->assertArrayNotHasKey('token', $query);
    }

    public function testGoogleRoutesAreNotRegisteredWhenGoogleProviderIsDisabled(): void
    {
        config(['auth.sso_providers' => ['other' => 'Other']]);

        // reload the web routes with the changed configuration
        Route::setRoutes(new RouteCollection());
        Route::middleware('web')->group(base_path('routes/web.php'));
        Route::getRoutes()->refreshNameLookups();

        $this->assertFalse(Route::has('auth.google'));
        $this->get($this->authUrl('/auth/google'))->assertNotFound();
        $this->get('/auth/google/callback?code=authorization-code')->assertNotFound();
    }

    /**
     * @return array<string, string>
     */
    private function sessionUrls(): array
    {
        return [
            GoogleController::SUCCESS_URL_KEY => self::SUCCESS_URL,
            GoogleController::ERROR_URL_KEY => self::ERROR_URL,
        ];
    }
}
