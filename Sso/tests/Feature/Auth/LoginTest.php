<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // tests must not depend on the providers enabled by the application - use a single fake one
        config([
            'auth.sso_providers' => ['test' => 'Test'],
            'auth.defaults.sso_provider' => null,
        ]);
        Route::get('auth/test', fn () => 'provider login')->name('auth.test');
        Route::getRoutes()->refreshNameLookups();
    }

    public function testLoginRequiresSuccessUrl(): void
    {
        $this->get('/auth/login?errorUrl=' . urlencode(self::ERROR_URL))
            ->assertStatus(400);
    }

    public function testLoginRequiresErrorUrl(): void
    {
        $this->get('/auth/login?successUrl=' . urlencode(self::SUCCESS_URL))
            ->assertStatus(400);
    }

    public function testLoginRedirectsToTheOnlyProvider(): void
    {
        [$url, $query] = $this->parseRedirect($this->get($this->authUrl('/auth/login')));

        $this->assertSame(route('auth.test'), $url);
        $this->assertSame([
            'successUrl' => self::SUCCESS_URL,
            'errorUrl' => self::ERROR_URL,
        ], $query);
    }

    #[DataProvider('untrustedUrls')]
    public function testLoginRejectsUntrustedSuccessUrl(string $url): void
    {
        $this->get($this->authUrl('/auth/login', $url))
            ->assertStatus(400);
    }

    #[DataProvider('untrustedUrls')]
    public function testLoginRejectsUntrustedErrorUrl(string $url): void
    {
        $this->get($this->authUrl('/auth/login', self::SUCCESS_URL, $url))
            ->assertStatus(400);
    }

    #[DataProvider('untrustedUrls')]
    public function testLoggedInUserDoesNotGetTokenForUntrustedSuccessUrl(string $url): void
    {
        // the URL is rejected before the session user is checked, so no database is needed
        $user = new User();
        $user->id = 1;
        $user->email = 'editor@example.com';

        $response = $this
            ->withSession([User::USER_SUBJECT_SESSION_KEY => $user])
            ->get($this->authUrl('/auth/login', $url));

        $response->assertStatus(400);
        $this->assertFalse($response->headers->has('Location'), 'Rejected request must not redirect anywhere');
    }
}
