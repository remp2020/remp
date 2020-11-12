<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Replace\UtmReplace;

class UtmReplaceTest extends TestCase
{
    /** @var UtmReplace */
    protected $utmReplace;

    public function setUp(): void
    {
        $this->utmReplace = new UtmReplace("demo-weekly-newsletter", "email", "impresa_mail_20190903103350", "");
    }

    public function testAddUtmParametersWhenTheUrlDoesNotContainAnyUTM()
    {
        $content = $this->utmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao" target="blank"/>');
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=" target="blank"/>',
            $content
        );
    }

    public function testReplaceExistingUTMParametersWhenTheUrlAlreadyContainsTheseUtm() {
        $content = $this->utmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_content=apple&amp;feira=terca&modelo=1?a=1&b=2" target="blank"/>');
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_content=&feira=terca&modelo=1%3Fa%3D1&b=2&utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350" target="blank"/>',
            $content
        );
    }

    public function testFixHTMLEntitiesFromURL() {
        $content = $this->utmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_content=apple&amp;feira=terca&modelo=1?a=1&b=2" target="blank"/>');
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_content=&feira=terca&modelo=1%3Fa%3D1&b=2&utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350" target="blank"/>',
            $content
        );
    }

    public function testPreserveTheParametersThatIsNotUTM() {
        $content = $this->utmReplace->replace('<a href="https://expresso.pt/html/que-nao-entrou-em-acao?feira=terca&modelo=1&a=1&b=2" target="blank"/>');
        $this->assertEquals(
            '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?feira=terca&modelo=1&a=1&b=2&utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=" target="blank"/>',
            $content
        );
    }

    public function testPreserveAdditionalAnchorAttributes() {
        $content = $this->utmReplace->replace('<a style="color: red" data-foo="bar" href="https://expresso.pt/" target="blank"/>');
        $this->assertEquals(
            '<a style="color: red" data-foo="bar" href="https://expresso.pt/?utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=" target="blank"/>',
            $content
        );
    }

    public function testPreserveMailtoLinks() {
        $content = $this->utmReplace->replace('<a href="mailto:admin@example.com"/>');
        $this->assertEquals(
            '<a href="mailto:admin@example.com"/>',
            $content
        );
    }

    public function testPreserveMailgunVariablesInUrl()
    {
        $url = $this->utmReplace->replaceUrl('https://predplatne.dennikn.sk/email-settings');
        $this->assertEquals('https://predplatne.dennikn.sk/email-settings?utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=', $url);

        $url = $this->utmReplace->replaceUrl('https://predplatne.dennikn.sk/email-settings?%recipient.autologin%');
        $this->assertEquals('https://predplatne.dennikn.sk/email-settings?%recipient.autologin%&utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=', $url);
    }

}
