<?php
declare(strict_types=1);

namespace Tests\Unit\ContentGenerator;

use PHPUnit\Framework\TestCase;
use Remp\Mailer\Models\ContentGenerator\SettingsTwigExtension;
use Remp\MailerModule\Models\ContentGenerator\Engine\TwigEngine;
use Twig\Error\RuntimeError;

class SettingsTwigExtensionTest extends TestCase
{
    private TwigEngine $twigEngine;

    protected function setUp(): void
    {
        $this->twigEngine = new TwigEngine();
        $this->twigEngine->addExtension(new SettingsTwigExtension());
    }

    public function testParseSettingsSnippet(): void
    {
        $snippet = <<<'SNIPPET'
{#
NEWSLETTER SETTINGS — documentation:

  title — "Graf dňa", shown in <title> and h1.
  show_author — true/false.
#}
  "title": "Graf dňa",
  "logo_url": "https://img.projektn.sk/wp-static/2024/07/ekonomicky-newsfilter.png",
  "show_author": true,
  "header_text": "",
  "header_icon": false,
  "footer_text": "Rubrika <a href=\"https://e.dennikn.sk/tema/graf-dna#top\">Graf dňa</a>."
SNIPPET;
        $template = '{% set newsletter = include("dn3-settings")|parse_settings %}'
            . '{{ newsletter.title }}|'
            . '{{ newsletter.logo_url }}|'
            . '{{ newsletter.show_author ? "yes" : "no" }}|'
            . '{{ newsletter.header_text ? "yes" : "no" }}|'
            . '{{ newsletter.header_icon ? "yes" : "no" }}|'
            . '{{ newsletter.footer_text|raw }}';

        $output = $this->twigEngine->render($template, ['snippets' => ['dn3-settings' => $snippet]]);

        $this->assertSame(
            'Graf dňa|https://img.projektn.sk/wp-static/2024/07/ekonomicky-newsfilter.png|yes|no|no|'
            . 'Rubrika <a href="https://e.dennikn.sk/tema/graf-dna#top">Graf dňa</a>.',
            $output
        );
    }

    public function testParseSettingsUnescapedQuoteThrows(): void
    {
        // closing quote of href is not escaped, which ends the JSON string early
        $snippet = '"footer_text": "Rubrika <a href=\"https://e.dennikn.sk/tema/graf-dna">Graf dňa</a>."';

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Unable to parse settings');
        $this->twigEngine->render(
            '{% set newsletter = include("dn3-settings")|parse_settings %}',
            ['snippets' => ['dn3-settings' => $snippet]]
        );
    }

    public function testParseSettingsEmpty(): void
    {
        $this->assertSame([], (new SettingsTwigExtension())->parseSettings("\n  \n"));
    }
}
