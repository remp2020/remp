<?php

namespace Remp\CampaignModule\Tests\Unit\Rules;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Remp\CampaignModule\Rules\ContainsHtmlLink;

class ContainsHtmlLinkTest extends TestCase
{
    private ContainsHtmlLink $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new ContainsHtmlLink();
    }

    private function validate(mixed $value): ?string
    {
        $errorMessage = null;
        $fail = function (string $message) use (&$errorMessage) {
            $errorMessage = $message;
        };
        $this->rule->validate('terms', $value, $fail);
        return $errorMessage;
    }

    #[DataProvider('validHtmlLinkProvider')]
    public function testPassesWithValidHtmlLinks(string $value): void
    {
        $this->assertNull($this->validate($value));
    }

    public static function validHtmlLinkProvider(): array
    {
        return [
            'simpleLink' => ['<a href="#">Link</a>'],
            'linkWithUrl' => ['<a href="https://example.com">Terms</a>'],
            'linkWithTextAround' => ['By clicking Subscribe, you agree with <a href="#">Terms</a>'],
            'linkWithAttributes' => ['<a href="#" target="_blank" class="link">Terms</a>'],
            'multipleLinks' => ['<a href="#">One</a> and <a href="#">Two</a>'],
            'linkWithWhitespace' => ['< a href="#">Link</ a>'],
        ];
    }

    #[DataProvider('invalidHtmlLinkProvider')]
    public function testFailsWithInvalidHtmlLinks(mixed $value): void
    {
        $error = $this->validate($value);
        $this->assertNotNull($error);
        $this->assertStringContainsString('HTML link', $error);
    }

    public static function invalidHtmlLinkProvider(): array
    {
        return [
            'emptyString' => [''],
            'nullValue' => [null],
            'textWithoutLink' => ['By clicking Subscribe, you agree with Terms'],
            'htmlWithoutLink' => ['<em>Subscribe</em> to agree with <strong>Terms</strong>'],
            'incompleteAnchorTag' => ['<a href="#">Terms'],
            'justOpeningTag' => ['<a>'],
        ];
    }
}
