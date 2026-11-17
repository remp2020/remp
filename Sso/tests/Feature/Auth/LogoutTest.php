<?php

namespace Tests\Feature\Auth;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\LaravelSso\Contracts\Jwt\Guard;
use Remp\LaravelSso\Contracts\Jwt\User as JwtUser;
use Remp\LaravelSso\Contracts\SsoContract;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    public function testLogoutRedirectsBackToTrustedReferer(): void
    {
        $this->withHeader('Referer', 'https://beam.test/articles')
            ->get('/auth/logout')
            ->assertRedirect('https://beam.test/articles');
    }

    public function testLogoutDoesNotRedirectToUntrustedReferer(): void
    {
        $this->withHeader('Referer', 'https://attacker.test/page')
            ->get('/auth/logout')
            ->assertRedirect(route('sso.error'));
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function webLogoutReferers(): array
    {
        return [
            'SsoPage_ShouldRedirectToSsoDashboard' => [self::SSO_ORIGIN . '/api-tokens'],
            'SsoPageWithoutVerifyJwtToken_ShouldRedirectToSsoDashboard' => [self::SSO_ORIGIN . '/error'],
            'OtherTrustedApplication_ShouldRedirectToSsoDashboard' => ['https://beam.test/articles'],
            'UntrustedSite_ShouldRedirectToSsoDashboard' => ['https://attacker.test/page'],
            'NoReferer_ShouldRedirectToSsoDashboard' => [null],
        ];
    }

    #[DataProvider('webLogoutReferers')]
    public function testWebLogoutInvalidatesTokenOnTheNextRequest(?string $referer): void
    {
        $sso = Mockery::mock(SsoContract::class);
        $sso->shouldReceive('introspect')->with('admin-jwt')->andReturn([]);
        $sso->shouldReceive('invalidate')->once()->with('admin-jwt')->andReturn(['redirect' => route('auth.logout')]);
        $this->app->instance(SsoContract::class, $sso);

        $this->withSession([
            Guard::JWT_USER_SESSION => serialize(new JwtUser()),
            Guard::JWT_TOKEN_SESSION => 'admin-jwt',
        ]);
        if ($referer !== null) {
            $this->withHeader('Referer', $referer);
        }

        $response = $this->get('/auth/logout-web');

        // always to an SSO page protected by VerifyJwtToken, never back to the Referer
        $response->assertRedirect(url('/'));
        $response->assertSessionMissing(Guard::JWT_USER_SESSION);
        // VerifyJwtToken invalidates the token on the next request - it must stay in the session until then
        $response->assertSessionHas(Guard::JWT_TOKEN_SESSION, 'admin-jwt');

        $this->get('/')->assertRedirect(route('auth.logout'));
        $this->assertNull(session(Guard::JWT_TOKEN_SESSION));
    }
}
