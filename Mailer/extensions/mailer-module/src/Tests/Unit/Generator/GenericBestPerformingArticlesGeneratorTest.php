<?php
declare(strict_types=1);

namespace Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\GenericBestPerformingArticlesGenerator;
use Remp\MailerModule\Models\PageMeta\Content\GenericPageContent;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Nette\Database\Table\Selection;

class GenericBestPerformingArticlesGeneratorTest extends TestCase
{
    private $sourceTemplateRepository;

    private $engineFactory;

    protected function setUp(): void
    {
        $htmlContent = <<<TEMPLATE
<table>        
{% for url,item in items %}
    <tr>
        <td>{{ item.title }}</td>
        <td>{{ item.description }}</td>
        <td><img src="{{ item.image }}"></td>
        <td>{{ url }}</td>
    </tr>
{% endfor %}
</table>
TEMPLATE;

        $textContent = <<<TEMPLATE
{% for url,item in items %}
{{ item.title }}
{{ item.description }}
{{ url}}
{% endfor %}
TEMPLATE;

        $mailSourceTemplate = [
            "content_html" => $htmlContent,
            "content_text" => $textContent
        ];

        $this->sourceTemplateRepository = $this->createConfiguredMock(SourceTemplatesRepository::class, [
            'find' => new ActiveRow(
                $mailSourceTemplate,
                $this->createMock(Selection::class)
            )
        ]);

        $this->engineFactory = $GLOBALS['container']->getByType(EngineFactory::class);
    }

    public function testProcess()
    {
        $transport = new class() implements TransportInterface
        {
            public function getContent(string $url): ?string
            {
                return <<<HTML
<!DOCTYPE html>
<html lang="sk">
<head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# video: http://ogp.me/ns/video#">
    <meta charset="utf-8">
    <meta property="og:title" content="here_is_title">
    <meta property="og:description" content="here_is_description">
    <meta property="og:something" content="THIS_META_WONT_BE_IN_EMAIL">
    <meta property="og:url" content="https://www.tyzden.sk/nazory/48501/zachrani-vas-zachranka-bez-lekara/">
    <meta name="author" content="Andrej Bán">
    <meta property="og:image" content="https://page.com/someimage.jpg">
</head>
<body>
  <header>header</header>
  <div>THIS_TEXT_WONT_BE_IN_EMAIL</div>
  <footer>footer</footer>
  </body></html>
HTML;
            }
        };

        $generator = new GenericBestPerformingArticlesGenerator(
            $this->sourceTemplateRepository,
            new GenericPageContent($transport),
            $this->engineFactory
        );

        $testObj = [];
        $testObj['source_template_id'] = 1;
        $testObj['articles'] = "http://someurl.com";

        $output = $generator->process($testObj);

        $htmlOutput = $output['htmlContent'];
        $textOutput = $output['textContent'];

        self::assertStringContainsString("here_is_title", $htmlOutput);
        self::assertStringContainsString("here_is_title", $textOutput);

        self::assertStringContainsString("here_is_description", $htmlOutput);
        self::assertStringContainsString("here_is_description", $textOutput);

        self::assertStringContainsString('https://page.com/someimage.jpg', $htmlOutput);
        self::assertStringNotContainsString('https://page.com/someimage.jpg', $textOutput);

        self::assertStringNotContainsString('THIS_TEXT_WONT_BE_IN_EMAIL', $htmlOutput);
        self::assertStringNotContainsString('THIS_TEXT_WONT_BE_IN_EMAIL', $textOutput);

        self::assertStringNotContainsString('THIS_META_WONT_BE_IN_EMAIL', $htmlOutput);
        self::assertStringNotContainsString('THIS_META_WONT_BE_IN_EMAIL', $textOutput);
    }
}
