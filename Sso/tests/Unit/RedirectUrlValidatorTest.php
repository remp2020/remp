<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use App\RedirectUrlValidator;
use Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RedirectUrlValidatorTest extends TestCase
{
    private RedirectUrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new RedirectUrlValidator(
            ['https://beam.test', 'https://campaign.test:8195/', ' HTTPS://Mailer.test ', 'not-an-origin', 'https://sso.test'],
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function trustedUrls(): array
    {
        return [
            'OriginWithoutTrailingSlash_ShouldBeAllowed' => ['https://beam.test'],
            'OriginWithTrailingSlash_ShouldBeAllowed' => ['https://beam.test/'],
            'PathWithQuery_ShouldBeAllowed' => ['https://beam.test/articles?page=2'],
            'QueryDirectlyAfterOrigin_ShouldBeAllowed' => ['https://beam.test?page=2'],
            'FragmentDirectlyAfterOrigin_ShouldBeAllowed' => ['https://beam.test#top'],
            'OriginWithPort_ShouldBeAllowed' => ['https://campaign.test:8195/campaigns'],
            'NormalizedConfiguredOrigin_ShouldBeAllowed' => ['https://mailer.test/'],
            'CaseInsensitiveOrigin_ShouldBeAllowed' => ['HTTPS://BEAM.TEST/Articles'],
            'SsoAdministration_ShouldBeAllowed' => ['https://sso.test/api-tokens'],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function untrustedUrls(): array
    {
        return [
            'EmptyString_ShouldBeRejected' => [''],
            'Null_ShouldBeRejected' => [null],
            'Array_ShouldBeRejected' => [['https://beam.test/']],
            'ForeignHost_ShouldBeRejected' => ['https://attacker.test/'],
            'TrustedHostAsSubdomainOfForeignHost_ShouldBeRejected' => ['https://beam.test.attacker.test/'],
            'TrustedHostAsUserinfo_ShouldBeRejected' => ['https://beam.test@attacker.test/'],
            'TrustedHostFollowedByBackslash_ShouldBeRejected' => ['https://beam.test\\@attacker.test/'],
            'SchemeRelativeUrl_ShouldBeRejected' => ['//attacker.test/'],
            'JavascriptScheme_ShouldBeRejected' => ['javascript:alert(1)'],
            'HttpInsteadOfHttps_ShouldBeRejected' => ['http://beam.test/'],
            'TrustedHostWithDifferentPort_ShouldBeRejected' => ['https://beam.test:8443/'],
            'TrustedHostWithExplicitDefaultPort_ShouldBeRejected' => ['https://beam.test:443/'],
            'TrustedHostAsPrefixOfPort_ShouldBeRejected' => ['https://campaign.test:81950/'],
            'TrustedHostOnlyInQuery_ShouldBeRejected' => ['https://attacker.test/?next=https://beam.test/'],
            'NewlineInjection_ShouldBeRejected' => ["https://beam.test/\nhttps://attacker.test/"],
            'LeadingWhitespace_ShouldBeRejected' => [' https://beam.test/'],
            'RelativePath_ShouldBeRejected' => ['/api-tokens'],
            'InvalidConfiguredOrigin_ShouldBeRejected' => ['not-an-origin/'],
        ];
    }

    #[DataProvider('trustedUrls')]
    public function testTrustedUrlIsAllowed(string $url): void
    {
        $this->assertTrue($this->validator->isAllowed($url));
        $this->assertSame($url, $this->validator->ensureAllowed($url, 'successUrl'));
    }

    #[DataProvider('untrustedUrls')]
    public function testUntrustedUrlIsRejected(mixed $url): void
    {
        $this->assertFalse($this->validator->isAllowed($url));
    }

    public function testEnsureAllowedLogsAndRejectsUntrustedUrl(): void
    {
        $log = Log::spy();

        try {
            $this->validator->ensureAllowed('https://attacker.test/', 'successUrl');
            $this->fail('Untrusted URL must be rejected');
        } catch (BadRequestHttpException $e) {
            $this->assertSame('Untrusted successUrl', $e->getMessage());
        }

        $log->shouldHaveReceived('notice')->once()->with('SSO rejected untrusted redirect URL', [
            'parameter' => 'successUrl',
            'url' => 'https://attacker.test/',
        ]);
    }

    public function testFromConfigUsesAllowedRedirectOrigins(): void
    {
        config([
            'auth.allowed_redirect_origins' => ['https://beam.test'],
            'services.remp.linked.mailer.url' => 'https://mailer.test',
        ]);

        $validator = RedirectUrlValidator::fromConfig();

        $this->assertTrue($validator->isAllowed('https://beam.test/'));
        $this->assertFalse($validator->isAllowed('https://mailer.test/'));
    }

    public function testFromConfigWithOnlyInvalidOriginsAllowsNothing(): void
    {
        $log = Log::spy();
        config([
            // origins with a path are invalid - a misconfigured allowlist must not fall back to other addresses
            'auth.allowed_redirect_origins' => ['https://beam.test/login', 'not-an-origin'],
            'services.remp.beam.web_addr' => 'https://beam.test',
        ]);

        $validator = RedirectUrlValidator::fromConfig();

        $this->assertFalse($validator->isAllowed('https://beam.test/'));
        $log->shouldHaveReceived('warning')->twice();
        $log->shouldHaveReceived('error')->once();
    }

    public function testFromConfigIgnoresInvalidConfiguredOriginsOnly(): void
    {
        config([
            'auth.allowed_redirect_origins' => ['https://beam.test', 'https://campaign.test/login'],
            'services.remp.linked.mailer.url' => 'https://mailer.test',
        ]);

        $validator = RedirectUrlValidator::fromConfig();

        $this->assertTrue($validator->isAllowed('https://beam.test/'));
        $this->assertFalse($validator->isAllowed('https://campaign.test/login'));
        $this->assertFalse($validator->isAllowed('https://mailer.test/'));
    }

    public function testFromConfigLogsInvalidRempAddress(): void
    {
        $log = Log::spy();
        config([
            'auth.allowed_redirect_origins' => [],
            'services.remp.linked.beam.url' => 'https://example.test/beam',
            'services.remp.linked.campaign.url' => null,
            'services.remp.linked.mailer.url' => 'https://mailer.test',
            'services.remp.sso.web_addr' => null,
        ]);

        $validator = RedirectUrlValidator::fromConfig();

        $this->assertFalse($validator->isAllowed('https://example.test/beam/'));
        $this->assertTrue($validator->isAllowed('https://mailer.test/'));
        $log->shouldHaveReceived('warning')->once()->with('SSO ignored invalid allowed redirect origin', ['origin' => 'https://example.test/beam']);
    }

    public function testFromConfigFallsBackToRempAddresses(): void
    {
        config([
            'auth.allowed_redirect_origins' => [],
            'services.remp.linked.beam.url' => 'https://beam.test',
            'services.remp.linked.campaign.url' => 'https://campaign.test',
            'services.remp.linked.mailer.url' => null,
            'services.remp.sso.web_addr' => 'https://sso.test',
        ]);

        $validator = RedirectUrlValidator::fromConfig();

        $this->assertTrue($validator->isAllowed('https://beam.test/'));
        $this->assertTrue($validator->isAllowed('https://campaign.test/'));
        $this->assertTrue($validator->isAllowed('https://sso.test/api-tokens'));
        $this->assertFalse($validator->isAllowed('https://attacker.test/'));
    }
}
