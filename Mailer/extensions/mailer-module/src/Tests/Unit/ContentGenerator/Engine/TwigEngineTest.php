<?php
declare(strict_types=1);

namespace Tests\Unit\ContentGenerator\Engine;

use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Models\ContentGenerator\Engine\TwigEngine;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigEngineTest extends TestCase
{
    public function testAddedExtensionIsAvailableInTemplates(): void
    {
        $twigEngine = new TwigEngine();
        $twigEngine->addExtension(new class extends AbstractExtension {
            public function getFilters(): array
            {
                return [new TwigFilter('shout', fn (string $value) => strtoupper($value) . '!')];
            }
        });

        $this->assertSame('HELLO!', $twigEngine->render('{{ greeting|shout }}', ['greeting' => 'hello']));
        // extensions apply to every render, not only the first one
        $this->assertSame('BYE!', $twigEngine->render('{{ "bye"|shout }}'));
    }
}
