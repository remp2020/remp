<?php
declare(strict_types=1);

namespace Tests\Unit\PageMeta;

use Nette\Utils\Strings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Models\PageMeta\Content\JsonLDContent;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;

class JsonLDContentTest extends TestCase
{
    private function content(): JsonLDContent
    {
        return new JsonLDContent($this->createStub(TransportInterface::class));
    }

    private function page(string $schema): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <script type="application/ld+json">{$schema}</script>
</head>
<body>article</body>
</html>
HTML;
    }

    private const AUDIO = <<<JSON
        "audio": {
            "@type": "AudioObject",
            "contentUrl": "https://a-static.projektn.sk/2026/09/SNF11092026mixdown.mp3",
            "contentSize": 13496769,
            "duration": "PT0H14M4S",
            "thumbnail": {
                "@type": "ImageObject",
                "url": "https://img.projektn.sk/wp-static/2024/07/newsfilter.png",
                "width": 240,
                "height": 240
            }
        },
JSON;

    private function articleSchema(string $extra = ''): string
    {
        return <<<JSON
{
    "@context": "http://schema.org",
    "@type": "NewsArticle",
    "headline": "Svetový newsfilter: Merz po výhre AfD prešiel do protiofenzívy",
    "description": "Správy spracovali Rastislav Kačmár, Tomáš Čorej a Mirek Tóda.",
    {$extra}
    "author": [
        {"@type": "Person", "name": "Rastislav Kačmár"},
        {"@type": "Person", "name": "Mirek Tóda"}
    ],
    "image": {
        "@type": "ImageObject",
        "url": "https://img.projektn.sk/wp-static/2026/09/MixCollage.jpg",
        "width": 1440,
        "height": 1080
    }
}
JSON;
    }

    public function testParsesAudio(): void
    {
        $meta = $this->content()->parseMeta($this->page($this->articleSchema(self::AUDIO)));

        $audio = $meta->getAudio();
        $this->assertNotNull($audio);
        $this->assertSame('https://a-static.projektn.sk/2026/09/SNF11092026mixdown.mp3', $audio->getUrl());
        $this->assertSame(13496769, $audio->getSize());
        $this->assertSame(844, $audio->getDurationInSeconds());
        $this->assertSame('https://img.projektn.sk/wp-static/2024/07/newsfilter.png', $audio->getThumbnailUrl());

        // the original fields keep working
        $this->assertSame('Svetový newsfilter: Merz po výhre AfD prešiel do protiofenzívy', $meta->getTitle());
        $this->assertSame('Správy spracovali Rastislav Kačmár, Tomáš Čorej a Mirek Tóda.', $meta->getDescription());
        $this->assertSame('https://img.projektn.sk/wp-static/2026/09/MixCollage.jpg', $meta->getImage());
        $this->assertSame(['Rastislav Kačmár', 'Mirek Tóda'], $meta->getAuthors());
    }

    public function testParsesFirstAudioOfAnArray(): void
    {
        $schema = $this->articleSchema(<<<JSON
        "audio": [
            {"@type": "AudioObject", "contentUrl": "https://example.com/first.mp3"},
            {"@type": "AudioObject", "contentUrl": "https://example.com/second.mp3"}
        ],
JSON);

        $audio = $this->content()->parseMeta($this->page($schema))->getAudio();
        $this->assertSame('https://example.com/first.mp3', $audio->getUrl());
    }

    public function testNoAudioInSchema(): void
    {
        $meta = $this->content()->parseMeta($this->page($this->articleSchema()));
        $this->assertNull($meta->getAudio());
    }

    public function testAudioWithoutContentUrlIsIgnored(): void
    {
        $schema = $this->articleSchema('"audio": {"@type": "AudioObject", "duration": "PT14M4S"},');
        $this->assertNull($this->content()->parseMeta($this->page($schema))->getAudio());
    }

    public function testUnparseableDuration(): void
    {
        $schema = $this->articleSchema('"audio": {"contentUrl": "https://example.com/a.mp3", "duration": "14 minutes"},');

        $audio = $this->content()->parseMeta($this->page($schema))->getAudio();
        $this->assertNull($audio->getDurationInSeconds());
        $this->assertSame('https://example.com/a.mp3', $audio->getUrl());
    }

    public function testNonNumericContentSize(): void
    {
        $schema = $this->articleSchema('"audio": {"contentUrl": "https://example.com/a.mp3", "contentSize": "13.5 MB"},');

        $audio = $this->content()->parseMeta($this->page($schema))->getAudio();
        $this->assertNull($audio->getSize());
        $this->assertSame('https://example.com/a.mp3', $audio->getUrl());
    }

    public static function imageSchemaProvider(): array
    {
        return [
            'object' => ['{"@type": "ImageObject", "url": "https://example.com/i.jpg"}'],
            'array' => ['[{"@type": "ImageObject", "url": "https://example.com/i.jpg"}]'],
            'plain string' => ['"https://example.com/i.jpg"'],
        ];
    }

    #[DataProvider('imageSchemaProvider')]
    public function testImageShapes(string $image): void
    {
        $schema = <<<JSON
{
    "@context": "http://schema.org",
    "@type": "NewsArticle",
    "headline": "headline",
    "image": {$image}
}
JSON;

        $meta = $this->content()->parseMeta($this->page($schema));
        $this->assertSame('https://example.com/i.jpg', $meta->getImage());
    }

    public function testSingleAuthorObject(): void
    {
        $schema = '{"@type": "NewsArticle", "headline": "h", "author": {"@type": "Person", "name": "Mirek Tóda"}}';

        $meta = $this->content()->parseMeta($this->page($schema));
        $this->assertSame(['Mirek Tóda'], $meta->getAuthors());
    }

    public function testMissingSchema(): void
    {
        $this->assertNull($this->content()->parseMeta('<html><body>no schema here</body></html>'));
    }
}
