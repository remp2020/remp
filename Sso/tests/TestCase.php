<?php

namespace Tests;

use App\RedirectUrlValidator;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected const SSO_ORIGIN = 'https://sso.test';

    protected const SUCCESS_URL = 'https://beam.test/';

    protected const ERROR_URL = 'https://beam.test/error';

    protected function setUp(): void
    {
        parent::setUp();

        // tests must not depend on the redirect origins configured by the application
        config([
            'auth.allowed_redirect_origins' => ['https://beam.test', 'https://campaign.test:8195', self::SSO_ORIGIN],
            'services.remp.sso.web_addr' => self::SSO_ORIGIN,
        ]);
        $this->app->forgetInstance(RedirectUrlValidator::class);
    }

    /**
     * Asserts the response is a redirect and splits its target into the URL without query and the query parameters.
     *
     * @param TestResponse<Response> $response
     * @return array{0: string, 1: array<mixed>}
     */
    protected function parseRedirect(TestResponse $response): array
    {
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return [explode('?', $location, 2)[0], $query];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function untrustedUrls(): array
    {
        return [
            'ForeignHost_ShouldBeRejected' => ['https://attacker.test/'],
            'TrustedHostAsUserinfo_ShouldBeRejected' => ['https://beam.test@attacker.test/'],
            'SchemeRelativeUrl_ShouldBeRejected' => ['//attacker.test/'],
        ];
    }

    protected function authUrl(string $path, string $successUrl = self::SUCCESS_URL, string $errorUrl = self::ERROR_URL): string
    {
        return $path . '?' . http_build_query([
            'successUrl' => $successUrl,
            'errorUrl' => $errorUrl,
        ]);
    }
}
