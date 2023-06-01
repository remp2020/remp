<?php

namespace Unit\ContentGenerator\Replace;

use Nette\DI\Container;
use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Models\ContentGenerator\Replace\AnchorRtmClickReplace;
use Remp\MailerModule\Models\ContentGenerator\Replace\RtmClickReplace;

class RtmClickReplaceTest extends TestCase
{
    protected AnchorRtmClickReplace $rtmClickReplace;

    public function setUp(): void
    {
        /** @var Container $container */
        $container = $GLOBALS['container'];

        $this->rtmClickReplace = $container->getByType(AnchorRtmClickReplace::class);
    }


    public function testRemoveQueryParams()
    {
        $url = $this->rtmClickReplace->removeQueryParams('https://expresso.pt/html/que-nao-entrou-em-acao');
        $this->assertEquals(
            'https://expresso.pt/html/que-nao-entrou-em-acao',
            $url
        );

        $url = $this->rtmClickReplace->removeQueryParams('https://expresso.pt/html/que-nao-entrou-em-acao?rtm_click=1234');
        $this->assertEquals(
            'https://expresso.pt/html/que-nao-entrou-em-acao',
            $url
        );

        $url = $this->rtmClickReplace->removeQueryParams('https://expresso.pt/html/que-nao-entrou-em-acao?rtm_click=1234&amp;rtm_content=');
        $this->assertEquals(
            'https://expresso.pt/html/que-nao-entrou-em-acao',
            $url
        );

        $url = $this->rtmClickReplace->removeQueryParams('https://expresso.pt/html/que-nao-entrou-em-acao?%recipeint.autologin%&amp;rtm_content=');
        $this->assertEquals(
            'https://expresso.pt/html/que-nao-entrou-em-acao',
            $url
        );
    }

    public function testSetRtmClickHashInUrl()
    {
        $url = $this->rtmClickReplace->setRtmClickHashInUrl('https://expresso.pt/html/que-nao-entrou-em-acao', '1234');
        $this->assertEquals(
            sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?%s=1234', RtmClickReplace::HASH_PARAM),
            $url
        );

        $url = $this->rtmClickReplace->setRtmClickHashInUrl(sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?%s=1234', RtmClickReplace::HASH_PARAM), '9876');
        $this->assertEquals(
            sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?%s=9876', RtmClickReplace::HASH_PARAM),
            $url
        );

        $url = $this->rtmClickReplace->setRtmClickHashInUrl('https://expresso.pt/html/que-nao-entrou-em-acao?rtm_content=123&rtm_source=', '9876');
        $this->assertEquals(
            sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?rtm_content=123&rtm_source=&%s=9876', RtmClickReplace::HASH_PARAM),
            $url
        );

        $url = $this->rtmClickReplace->setRtmClickHashInUrl(sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?%s=1234&rtm_content=123&rtm_source=', RtmClickReplace::HASH_PARAM), '9876');
        $this->assertEquals(
            sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?rtm_content=123&rtm_source=&%s=9876', RtmClickReplace::HASH_PARAM),
            $url
        );

        $url = $this->rtmClickReplace->setRtmClickHashInUrl('https://expresso.pt/html/que-nao-entrou-em-acao?%recipient.autologin%', '9876');
        $this->assertEquals(
            sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?%%recipient.autologin%%&%s=9876', RtmClickReplace::HASH_PARAM),
            $url
        );
    }

    public function testGetRtmClickHash()
    {
        $hash = $this->rtmClickReplace->getRtmClickHashFromUrl('https://expresso.pt/html/que-nao-entrou-em-acao');
        $this->assertEquals(
            null,
            $hash
        );

        $hash = $this->rtmClickReplace->getRtmClickHashFromUrl(sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?%s=1234', RtmClickReplace::HASH_PARAM));
        $this->assertEquals(
            '1234',
            $hash
        );

        $hash = $this->rtmClickReplace->getRtmClickHashFromUrl(sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?%s=1234&amp;rtm_content=qwert', RtmClickReplace::HASH_PARAM));
        $this->assertEquals(
            '1234',
            $hash
        );

        $hash = $this->rtmClickReplace->getRtmClickHashFromUrl(sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?%%recipient.autologin%%&%s=1234&amp;rtm_content=qwert', RtmClickReplace::HASH_PARAM));
        $this->assertEquals(
            '1234',
            $hash
        );

        $hash = $this->rtmClickReplace->getRtmClickHashFromUrl(sprintf('https://expresso.pt/html/que-nao-entrou-em-acao?rtm_source=&amp;%s=1234', RtmClickReplace::HASH_PARAM));
        $this->assertEquals(
            null,
            $hash
        );
    }
}
