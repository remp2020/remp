<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\GoogleController;
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
}
