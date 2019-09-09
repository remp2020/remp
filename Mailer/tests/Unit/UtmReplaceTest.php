<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Replace\UtmReplace;

class UtmReplaceTest extends TestCase
{
    protected $utmReplace;

    public function setUp()
    {
        // GIVEN || ARRANGE
        $this->utmReplace = new UtmReplace("demo-weekly-newsletter", "email", "impresa_mail_20190903103350", "");
    }

    public function testAddUtmParametersWhenTheUrlDoesNotContainAnyUTM()
    {
        //WHEN || ACTION
        $content = $this->utmReplace->replace("<a href=\"https://expresso.pt/html/que-nao-entrou-em-acao\" target=\"blank\"/>");

        //THEN || ASSERT
        $this->assertTrue(true,'<a href=\"https://expresso.pt/html/que-nao-entrou-em-acao?utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=\" target=\"blank\"/>');
    }

    public function testReplaceExistingUTMParametersWhenTheUrlAlreadyContainsTheseUtm() {
        //WHEN || ACTION
        $content = $this->utmReplace->replace("<a href=\"https://expresso.pt/html/que-nao-entrou-em-acao?utm_content=apple&amp;feira=terca&modelo=1?a=1&b=2\" target=\"blank\"/>");

        //THEN || ASSERT
        $this->assertTrue($content ===  '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=&feira=terca&modelo=1%3Fa%3D1&b=2" target="blank"/>');
    }

    public function testFixHTMLEntitiesFromURL() {
        //WHEN || ACTION
        $content = $this->utmReplace->replace("<a href=\"https://expresso.pt/html/que-nao-entrou-em-acao?utm_content=apple&amp;feira=terca&modelo=1?a=1&b=2\" target=\"blank\"/>");

        //THEN || ASSERT
        $this->assertTrue($content ===  '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=&feira=terca&modelo=1%3Fa%3D1&b=2" target="blank"/>');
    }

    public function testPreserveTheParametersThatIsNotUTM() {
        //WHEN || ACTION
        $content = $this->utmReplace->replace("<a href=\"https://expresso.pt/html/que-nao-entrou-em-acao?feira=terca&modelo=1&a=1&b=2\" target=\"blank\"/>");

        //THEN || ASSERT
        $this->assertTrue($content ===  '<a href="https://expresso.pt/html/que-nao-entrou-em-acao?utm_source=demo-weekly-newsletter&utm_medium=email&utm_campaign=impresa_mail_20190903103350&utm_content=&feira=terca&modelo=1&a=1&b=2" target="blank"/>');
    }



}
