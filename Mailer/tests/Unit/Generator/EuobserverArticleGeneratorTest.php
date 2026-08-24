<?php
declare(strict_types=1);

namespace Tests\Unit\Generator;

use Nette\Database\Table\Selection;
use PHPUnit\Framework\TestCase;
use Remp\Mailer\Models\Generators\EmbedParser;
use Remp\Mailer\Models\Generators\Euobserver\ArticleGenerator;
use Remp\Mailer\Models\Generators\EuobserverArticleLocker;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;

class EuobserverArticleGeneratorTest extends TestCase
{
    private const string CTA_SNIPPET = "{{ include('eo-button-red'";

    private const string ARTICLE_HTML = <<<HTML
        <!-- wp:paragraph --><p>BEFORE-LOCK</p><!-- /wp:paragraph -->
        <!-- wp:eo/lock -->
        <!-- /wp:eo/lock -->
        <!-- wp:paragraph --><p>AFTER-LOCK</p><!-- /wp:paragraph -->
        HTML;

    private function process(bool $lockingEnabled, string $articleHtml): array
    {
        $sourceTemplateRepository = $this->createConfiguredStub(SourceTemplatesRepository::class, [
            'find' => new ActiveRow([
                'content_html' => '{{ html|raw }}',
                'content_text' => '{{ text|raw }}',
            ], $this->createStub(Selection::class)),
        ]);

        $generator = new ArticleGenerator(
            $sourceTemplateRepository,
            $this->createStub(ContentInterface::class),
            $this->createStub(EmbedParser::class),
            $GLOBALS['container']->getByType(EngineFactory::class),
            new EuobserverArticleLocker(),
            $lockingEnabled,
        );

        return $generator->process([
            'source_template_id' => 1,
            'article_html' => $articleHtml,
            'title' => 'This week',
            'url' => 'https://euobserver.com/newsletter/agenda/1/',
            'editor' => 'Editor',
        ]);
    }

    public function testLockedVariantCutAtLockBlock(): void
    {
        $output = $this->process(true, self::ARTICLE_HTML);

        self::assertStringContainsString('BEFORE-LOCK', $output['htmlContent']);
        self::assertStringContainsString('AFTER-LOCK', $output['htmlContent']);
        self::assertStringNotContainsString(self::CTA_SNIPPET, $output['htmlContent']);
        self::assertStringNotContainsString('wp:', $output['htmlContent']);

        self::assertStringContainsString('BEFORE-LOCK', $output['lockedHtmlContent']);
        self::assertStringNotContainsString('AFTER-LOCK', $output['lockedHtmlContent']);
        self::assertStringContainsString(self::CTA_SNIPPET, $output['lockedHtmlContent']);
        self::assertStringNotContainsString('wp:', $output['lockedHtmlContent']);

        self::assertStringNotContainsString('AFTER-LOCK', $output['lockedTextContent']);
        self::assertStringContainsString(self::CTA_SNIPPET, $output['lockedTextContent']);
    }

    public function testMissingLockBlockSendsFullContentToNonSubscribers(): void
    {
        $withoutLock = str_replace(['<!-- wp:eo/lock -->', '<!-- /wp:eo/lock -->'], '', self::ARTICLE_HTML);

        $output = $this->process(true, $withoutLock);

        self::assertStringContainsString('AFTER-LOCK', $output['lockedHtmlContent']);
        self::assertStringNotContainsString(self::CTA_SNIPPET, $output['lockedHtmlContent']); // no lock snippet if there's no lock
    }

    public function testLockingDisabledEmitsNoLockedVariant(): void
    {
        $output = $this->process(false, self::ARTICLE_HTML);

        self::assertStringContainsString('AFTER-LOCK', $output['htmlContent']);
        self::assertArrayNotHasKey('lockedHtmlContent', $output);
        self::assertArrayNotHasKey('lockedTextContent', $output);
    }
}
