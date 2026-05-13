<?php
declare(strict_types=1);

namespace Tests\Unit\ContentGenerator\Replace;

use Nette\Http\Url;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Remp\Mailer\Models\ContentGenerator\AnchorWirelinkReplace;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;
use Remp\MailerModule\Models\ContentGenerator\Replace\RtmClickReplace;

class AnchorWirelinkReplaceTest extends TestCase
{
    private const WIRELINK_HOST = 'https://wirelink.dennikn.sk';

    private AnchorWirelinkReplace $wirelinkReplace;
    private GeneratorInput $generatorInput;

    protected function setUp(): void
    {
        $this->wirelinkReplace = new AnchorWirelinkReplace(self::WIRELINK_HOST, ['dennikn.sk', 'e.dennikn.sk']);
        $this->generatorInput = $this->createStub(GeneratorInput::class);
    }

    public static function replacedUrlProvider(): array
    {
        $wl = self::WIRELINK_HOST;
        return [
            'DoubleQuotedHref_IsReplaced' => [
                'input' => '<a href="https://dennikn.sk/article">',
                'expected' => '<a href="' . $wl . '/r/https%3A%2F%2Fdennikn.sk%2Farticle">',
            ],
            'AttributesBeforeHref_ArePreserved' => [
                'input' => '<a style="color:red" class="btn" href="https://dennikn.sk/">',
                'expected' => '<a style="color:red" class="btn" href="' . $wl . '/r/https%3A%2F%2Fdennikn.sk%2F">',
            ],
            'AttributesAfterHref_ArePreserved' => [
                'input' => '<a href="https://e.dennikn.sk/" target="_blank">',
                'expected' => '<a href="' . $wl . '/r/https%3A%2F%2Fe.dennikn.sk%2F" target="_blank">',
            ],
            'UrlQueryParameters_ArePreserved' => [
                'input' => '<a href="https://dennikn.sk/article?id=123&ref=email">',
                'expected' => '<a href="' . $wl . '/r/https%3A%2F%2Fdennikn.sk%2Farticle%3Fid%3D123%26ref%3Demail">',
            ],
            'SingleQuotedHref_IsReplaced' => [
                'input' => "<a href='https://dennikn.sk/article'>",
                'expected' => "<a href='" . $wl . "/r/https%3A%2F%2Fdennikn.sk%2Farticle'>",
            ],
            'AllowedDomainAmongMultipleLinks_IsReplaced' => [
                'input' => '<a href="https://dennikn.sk/article">link1</a> <a href="https://google.com/search">link2</a>',
                'expected' => '<a href="' . $wl . '/r/https%3A%2F%2Fdennikn.sk%2Farticle">link1</a> <a href="https://google.com/search">link2</a>',
            ],
            'MultilineAnchorTag_IsReplaced' => [
                'input' => '<a href="https://dennikn.sk/article" style="color:#ffffff;
                display:inline-block;">',
                'expected' => '<a href="' . $wl . '/r/https%3A%2F%2Fdennikn.sk%2Farticle" style="color:#ffffff;
                display:inline-block;">',
            ],
        ];
    }

    #[DataProvider('replacedUrlProvider')]
    public function testReplaces(string $input, string $expected): void
    {
        $this->assertEquals($expected, $this->wirelinkReplace->replace($input, $this->generatorInput));
    }

    public static function unchangedContentProvider(): array
    {
        return [
            'NonAllowedDomain_IsSkipped' => ['input' => '<a href="https://google.com/search">'],
            'MailtoLink_IsSkipped' => ['input' => '<a href="mailto:admin@example.com">'],
            'ContentWithNoLinks_IsUnchanged' => ['input' => '<p>Hello world</p>'],
        ];
    }

    #[DataProvider('unchangedContentProvider')]
    public function testSkips(string $input): void
    {
        $this->assertEquals($input, $this->wirelinkReplace->replace($input, $this->generatorInput));
    }

    public function testSkipsAllUrlsWhenNoDomainsAreAllowed(): void
    {
        $replacer = new AnchorWirelinkReplace(self::WIRELINK_HOST, []);

        $input = '<a href="https://dennikn.sk/article">';
        $this->assertEquals($input, $replacer->replace($input, $this->generatorInput));
    }

    public function testLiftsRtmClickToOuterWirelinkUrl(): void
    {
        $hash = 'abc123';
        $input = '<a href="https://dennikn.sk/article?foo=bar&rtm_click=' . $hash . '">Read</a>';

        $result = $this->wirelinkReplace->replace($input, $this->generatorInput);

        $matches = [];
        $this->assertSame(1, preg_match('/href="([^"]+)"/', $result, $matches));

        $wirelinkUrl = new Url($matches[1]);

        $this->assertSame('wirelink.dennikn.sk', $wirelinkUrl->getHost());
        $this->assertSame($hash, $wirelinkUrl->getQueryParameter(RtmClickReplace::HASH_PARAM));
        $this->assertSame('/r/https%3A%2F%2Fdennikn.sk%2Farticle%3Ffoo%3Dbar', $wirelinkUrl->getPath());
    }
}
