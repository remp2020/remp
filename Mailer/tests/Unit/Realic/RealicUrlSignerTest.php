<?php
declare(strict_types=1);

namespace Tests\Unit\Realic;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Remp\Mailer\Models\Realic\RealicUrlSigner;

class RealicUrlSignerTest extends TestCase
{
    private const KEY = 'secret';

    private const TARGET_HOST = 'img.dennikn.sk';

    private const RETIRED_HOST = 'img.projektn.sk';

    private const BUCKET = 'wp-static';

    private const ORIGIN_HOST = 'a-static.projektn.sk';

    /**
     * Known-answer vectors from realic's README (REALIC_SIGN_KEY=secret).
     */
    public static function readmeVectors(): array
    {
        return [
            'no params' => [
                '/wp-static/wp-content/uploads/2026/01/photo.jpg',
                '029c526ccb2f45878f57ffa29a02a1aa',
            ],
            'params sorted by key' => [
                '/wp-static/wp-content/uploads/2026/01/photo.jpg?w=800&q=90&h=600',
                '8b57f60b3abcc06fa08f236246d8c146',
            ],
            'comma encoded in signed query' => [
                '/wp-static/wp-content/uploads/2026/01/photo.jpg?crop=1200,800,100,50&w=600',
                'd35239a555fcebba608dd4a43365a23b',
            ],
            'empty value is still a param' => [
                '/wp-static/wp-content/uploads/2026/01/photo.jpg?w=&h=600',
                'c7c80ae68372b9d7aeb53dcd98454f7f',
            ],
            'space in path signs decoded' => [
                '/wp-static/wp-content/uploads/2026/01/foto%2001.jpg?w=800',
                '9603180c53ac38b9f5f82a4d1889029e',
            ],
            'space in query signs as plus' => [
                '/wp-static/wp-content/uploads/2026/01/photo.jpg?mark=logo%20white.png&markw=20',
                'de6041f016df517b4385da037eb2096d',
            ],
            'non-ascii path' => [
                '/wp-static/wp-content/uploads/2026/01/kov%C3%A1%C4%8D-%C5%A1%C3%A9f.jpg?w=800',
                'aad1113f06fb453795b28e833fb638f4',
            ],
            'encoded separator decodes before signing' => [
                '/wp-static/wp-content/uploads/2026%2F01/photo.jpg?w=800',
                '4e454e39df81f72f02dc2af0ab9ee5da',
            ],
        ];
    }

    #[DataProvider('readmeVectors')]
    public function testSignMatchesRealicVectors(string $path, string $expectedSignature): void
    {
        $signed = $this->signerWithoutOrigin()->sign('https://' . self::TARGET_HOST . $path);

        parse_str((string) parse_url($signed, PHP_URL_QUERY), $params);
        $this->assertSame($expectedSignature, $params['s']);
    }

    public function testSignProducesReadmeExampleUrl(): void
    {
        $this->assertSame(
            'https://img.dennikn.sk/wp-static/2026/06/RussiaUkraineWar415037.jpg'
                . '?w=720&h=480&fit=crop&fm=jpg&q=81&s=fdf2e3db1fbc71ce453ca028a37fcd93',
            $this->signer()->sign(
                'https://img.dennikn.sk/wp-static/2026/06/RussiaUkraineWar415037.jpg'
                    . '?w=720&h=480&fit=crop&fm=jpg&q=81',
            ),
        );
    }

    public function testSignMigratesRetiredHost(): void
    {
        $this->assertSame(
            $this->signer()->sign('https://' . self::TARGET_HOST . '/wp-static/a.jpg?w=400'),
            $this->signer()->sign('https://' . self::RETIRED_HOST . '/wp-static/a.jpg?w=400'),
        );
    }

    public function testSignReplacesStaleSignature(): void
    {
        $signer = $this->signer();
        $url = 'https://' . self::TARGET_HOST . '/wp-static/a.jpg?w=400';

        $this->assertSame($signer->sign($url), $signer->sign($url . '&s=deadbeef'));
    }

    public function testSignSendsParamlessUrlToBucketOrigin(): void
    {
        $this->assertSame(
            'https://a-static.projektn.sk/2023/10/jpeg-optimizer1.jpg',
            $this->signer()->sign('https://img.projektn.sk/wp-static/2023/10/jpeg-optimizer1.jpg'),
        );
    }

    public function testSignSendsAlreadySignedParamlessUrlToBucketOrigin(): void
    {
        $this->assertSame(
            'https://a-static.projektn.sk/2023/10/a.jpg',
            $this->signer()->sign('https://img.dennikn.sk/wp-static/2023/10/a.jpg?s=deadbeef'),
        );
    }

    public function testSignKeepsParamlessUrlOfBucketWithNoOriginOnResizer(): void
    {
        $url = 'https://img.dennikn.sk/shotmajster/a.jpg';

        $this->assertStringStartsWith('https://img.dennikn.sk/shotmajster/a.jpg?s=', $this->signer()->sign($url));
    }

    public function testSignResolvesTheOriginPerBucket(): void
    {
        $signer = new RealicUrlSigner(self::KEY, self::TARGET_HOST, [], [
            'wp-static' => 'a-static.projektn.sk',
            'shotmajster' => 'shots.example.org',
        ]);

        $this->assertSame(
            'https://a-static.projektn.sk/2023/10/a.jpg',
            $signer->sign('https://img.dennikn.sk/wp-static/2023/10/a.jpg'),
        );
        $this->assertSame(
            'https://shots.example.org/2023/10/a.jpg',
            $signer->sign('https://img.dennikn.sk/shotmajster/2023/10/a.jpg'),
        );
    }

    public function testSignStillSignsBareBucketUrl(): void
    {
        $signed = $this->signer()->sign('https://img.dennikn.sk/wp-static/');

        $this->assertStringStartsWith('https://img.dennikn.sk/wp-static/?s=', $signed);
    }

    public function testSignStillSignsParamlessUrlWithoutBucketOriginConfigured(): void
    {
        $signed = $this->signerWithoutOrigin()->sign('https://img.projektn.sk/wp-static/a.jpg');

        $this->assertStringStartsWith('https://img.dennikn.sk/wp-static/a.jpg?s=', $signed);
    }

    public function testRewriteSendsParamlessUrlsToBucketOrigin(): void
    {
        $rewritten = $this->signer()->rewrite(
            '<img src="https://img.projektn.sk/wp-static/2023/10/a.jpg">'
            . '<img src="https://img.projektn.sk/wp-static/2023/10/b.jpg?w=400">',
        );

        $this->assertStringContainsString('<img src="https://a-static.projektn.sk/2023/10/a.jpg">', $rewritten);
        $this->assertStringContainsString('<img src="https://img.dennikn.sk/wp-static/2023/10/b.jpg?w=400&s=', $rewritten);
    }

    /**
     * Nothing stores an origin URL with a query -- the origin is a plain static server. Adding resize params
     * to one builds it as an intermediate, so signing has to route it back.
     */
    public function testSignSendsOriginUrlWithParamsBackToTheResizer(): void
    {
        $signed = $this->signer()->sign('https://a-static.projektn.sk/2026/06/a.jpg?w=280&h=280&fit=crop');

        $this->assertStringStartsWith(
            'https://img.dennikn.sk/wp-static/2026/06/a.jpg?w=280&h=280&fit=crop&s=',
            $signed,
        );
    }

    public function testSignLeavesParamlessOriginUrlAlone(): void
    {
        $url = 'https://a-static.projektn.sk/2026/06/a.jpg';

        $this->assertSame($url, $this->signer()->sign($url));
    }

    public function testSignLeavesOriginUrlAloneWithoutOriginHostsConfigured(): void
    {
        $url = 'https://a-static.projektn.sk/2026/06/a.jpg?w=280';

        $this->assertSame($url, $this->signerWithoutOrigin()->sign($url));
    }

    public function testSignLeavesForeignHostsAlone(): void
    {
        $url = 'https://img.novydenik.com/wp-static/a.jpg?w=400';

        $this->assertSame($url, $this->signer()->sign($url));
    }

    public function testRewriteSignsEveryHtmlContext(): void
    {
        $html = <<<HTML
            <img src="https://img.projektn.sk/wp-static/a.jpg" alt="a">
            <div style="background: url('https://img.projektn.sk/wp-static/b.jpg?w=500')"></div>
            <meta property="og:image" content="https://img.projektn.sk/wp-static/c.png?w=1000" />
            HTML;

        $rewritten = $this->signer()->rewrite($html);

        $this->assertStringNotContainsString(self::RETIRED_HOST, $rewritten);
        // a.jpg carries no params, so it goes to the bucket origin instead of being signed
        $this->assertSame(2, substr_count($rewritten, 's='));
        $this->assertStringContainsString('<img src="https://a-static.projektn.sk/a.jpg" alt="a">', $rewritten);
        $this->assertStringContainsString("url('https://img.dennikn.sk/wp-static/b.jpg?w=500&s=", $rewritten);
        $this->assertStringContainsString('content="https://img.dennikn.sk/wp-static/c.png?w=1000&s=', $rewritten);
    }

    public function testRewriteKeepsParenthesesInFilenames(): void
    {
        $rewritten = $this->signer()->rewrite(
            '<img src="https://img.projektn.sk/wp-static/2021/04/Photo-by-(C)-Phil-Coomes.jpg?w=690">',
        );

        // The whole filename survives; realic emits the parentheses percent-encoded too, and verifies
        // against the decoded path, so this is the same URL it signs.
        $this->assertStringStartsWith(
            '<img src="https://img.dennikn.sk/wp-static/2021/04/Photo-by-%28C%29-Phil-Coomes.jpg?w=690&s=',
            $rewritten,
        );
    }

    public function testRewriteStopsAtTheClosingQuoteOfACssUrl(): void
    {
        $rewritten = $this->signer()->rewrite(
            "background: url('https://img.projektn.sk/wp-static/a.jpg?w=500') no-repeat;",
        );

        $this->assertStringEndsWith("') no-repeat;", $rewritten);
    }

    public function testRewriteNormalisesEncodedSeparators(): void
    {
        $rewritten = $this->signer()->rewrite(
            '<img srcset="https://img.projektn.sk/wp-static/a.jpg?w=400&amp;fm=jpg 1x">',
        );

        // &amp; would parse as a param named "amp;fm", so it is decoded before signing and stays decoded.
        $this->assertStringNotContainsString('&amp;', $rewritten);
        $this->assertMatchesRegularExpression('~\?w=400&fm=jpg&s=[0-9a-f]{32} 1x~', $rewritten);
    }

    public function testRewriteSignsEncodedSeparatorsTheSameAsBareOnes(): void
    {
        $signer = $this->signer();

        $this->assertSame(
            $signer->rewrite('<img src="https://img.projektn.sk/wp-static/a.jpg?w=400&fm=jpg">'),
            $signer->rewrite('<img src="https://img.projektn.sk/wp-static/a.jpg?w=400&amp;fm=jpg">'),
        );
    }

    public function testRewriteSignsEachSrcsetCandidateSeparately(): void
    {
        $rewritten = $this->signer()->rewrite(
            '<img srcset="https://img.projektn.sk/wp-static/a.png?w=990 990w,'
            . ' https://img.projektn.sk/wp-static/a.png?w=600 600w">',
        );

        $this->assertSame(2, substr_count($rewritten, '&s='));
        $this->assertStringContainsString('?w=990&s=', $rewritten);
        $this->assertStringContainsString('?w=600&s=', $rewritten);
        $this->assertStringContainsString(' 990w, ', $rewritten);
        $this->assertStringContainsString(' 600w"', $rewritten);
    }

    public function testRewriteIsIdempotent(): void
    {
        $signer = $this->signer();
        $html = '<img src="https://img.projektn.sk/wp-static/a.jpg?w=400&amp;fm=jpg">'
            . '<img src="https://img.projektn.sk/wp-static/b.jpg">';

        $once = $signer->rewrite($html);

        $this->assertSame($once, $signer->rewrite($once));
    }

    private function signer(): RealicUrlSigner
    {
        return new RealicUrlSigner(
            self::KEY,
            self::TARGET_HOST,
            [self::RETIRED_HOST],
            [self::BUCKET => self::ORIGIN_HOST],
        );
    }

    private function signerWithoutOrigin(): RealicUrlSigner
    {
        return new RealicUrlSigner(self::KEY, self::TARGET_HOST, [self::RETIRED_HOST]);
    }
}
