<?php
declare(strict_types=1);

namespace Tests\Unit\Generator;

use Nette\Database\Table\Selection;
use PHPUnit\Framework\TestCase;
use Remp\Mailer\Models\Generators\EuobserverGenerator;
use Remp\Mailer\Models\Generators\EuobserverWordpressBlockParser;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;

class EuobserverGeneratorTest extends TestCase
{
    public function testEoAdvertBlockDoesNotBreakGeneration(): void
    {
        $engineFactory = $GLOBALS['container']->getByType(EngineFactory::class);
        $sourceTemplateRepository = $this->createConfiguredStub(SourceTemplatesRepository::class, [
            'find' => new ActiveRow([
                'content_html' => '{{ html|raw }}',
                'content_text' => '{{ text|raw }}',
            ], $this->createStub(Selection::class)),
        ]);

        $generator = new EuobserverGenerator(
            $sourceTemplateRepository,
            $engineFactory,
            new EuobserverWordpressBlockParser($engineFactory),
        );

        $output = $generator->process([
            'source_template_id' => 1,
            'blocks_json' => json_encode([
                ['name' => 'core/paragraph', 'attributes' => ['content' => 'BEFORE'], 'innerBlocks' => []],
                ['name' => 'eo/advert', 'attributes' => [], 'innerBlocks' => []],
                ['name' => 'core/paragraph', 'attributes' => ['content' => 'AFTER'], 'innerBlocks' => []],
            ]),
            'settings_json' => '{}',
            'from' => 'EUobserver <newsletter@euobserver.com>',
            'subject' => 'This week',
        ]);

        self::assertStringContainsString('BEFORE', $output['htmlContent']);
        self::assertStringContainsString('AFTER', $output['htmlContent']);
    }
}
