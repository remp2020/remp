<?php
declare(strict_types=1);

namespace Tests\Unit\ContentGenerator\Replace;

use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;
use Remp\MailerModule\Models\ContentGenerator\Replace\AnchorUtmReplace;
use Remp\MailerModule\Models\DataRow;

class UtmReplaceTest extends TestCase
{
    /** @var AnchorUtmReplace */
    protected $utmReplace;

    protected $generatorInput;

    public function setUp(): void
    {
        $this->utmReplace = new AnchorUtmReplace();

        $mailType = new DataRow([
            'id' => 1,
            'name' => 'type',
            'code' => 'demo-weekly-newsletter'
        ]);

        $mailLayout = new DataRow([
            'id' => 1,
            'name' => "layout1",
        ]);

        $mailTemplate = new DataRow([
            'name' => 'Tesst',
            'code' => 'impresa_mail_20190903103350',
            'description' => '',
            'from' => 'from',
            'autologin' => true,
            'subject' => 'subject',
            'mail_body_text' => 'textt',
            'mail_body_html' => 'html',
            'mail_layout_id' => $mailLayout->id,
            'mail_layout' => $mailLayout,
            'mail_type_id' => $mailType->id,
            'mail_type' => $mailType
        ]);

        $this->generatorInput = new GeneratorInput($mailTemplate);
    }

    public function testAddUtmParametersWhenTheUrlDoesNotContainAnyUTM()
    {
        $content = $this->utmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao" target="blank"/>', $this->generatorInput);
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=" target="blank"/>',
            $content
        );
    }

    public function testReplaceExistingUTMParametersWhenTheUrlAlreadyContainsTheseUtm()
    {
        $content = $this->utmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_content=apple&amp;feira=terca&modelo=1?a=1&b=2" target="blank"/>', $this->generatorInput);
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_content=&feira=terca&modelo=1%3Fa%3D1&b=2&utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350" target="blank"/>',
            $content
        );
    }

    public function testFixHTMLEntitiesFromURL()
    {
        $content = $this->utmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_content=apple&amp;feira=terca&modelo=1?a=1&b=2" target="blank"/>', $this->generatorInput);
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_content=&feira=terca&modelo=1%3Fa%3D1&b=2&utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350" target="blank"/>',
            $content
        );
    }

    public function testPreserveTheParametersThatIsNotUTM()
    {
        $content = $this->utmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao?feira=terca&modelo=1&a=1&b=2" target="blank"/>', $this->generatorInput);
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?feira=terca&modelo=1&a=1&b=2&utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=" target="blank"/>',
            $content
        );
    }

    public function testPreserveAdditionalAnchorAttributes()
    {
        $content = $this->utmReplace->replace('<a style="color: red" data-foo="bar" href="https://expresso.pt/" target="blank"/>', $this->generatorInput);
        $this->assertEquals(
            '<a style="color: red" data-foo="bar" href="https://expresso.pt/?utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=" target="blank"/>',
            $content
        );
    }

    public function testPreserveMailtoLinks()
    {
        $content = $this->utmReplace->replace('<a href="mailto:admin@example.com"/>', $this->generatorInput);
        $this->assertEquals(
            '<a href="mailto:admin@example.com"/>',
            $content
        );
    }

    public function testPreserveMailgunVariablesInUrl()
    {
        $url = $this->utmReplace->replaceUrl('https://predplatne.dennikn.sk/email-settings', $this->generatorInput);
        $this->assertEquals('https://predplatne.dennikn.sk/email-settings?utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=', $url);

        $url = $this->utmReplace->replaceUrl('https://predplatne.dennikn.sk/email-settings?%recipient.autologin%', $this->generatorInput);
        $this->assertEquals('https://predplatne.dennikn.sk/email-settings?%recipient.autologin%&utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=', $url);
    }
}
