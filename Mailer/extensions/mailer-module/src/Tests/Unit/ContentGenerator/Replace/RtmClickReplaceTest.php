<?php

namespace Unit\ContentGenerator\Replace;

use Nette\DI\Container;
use Nette\Http\Url;
use PHPUnit\Framework\Attributes\DataProvider;
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

        $hash = $this->rtmClickReplace->getRtmClickHashFromUrl(sprintf('https://expresso.pt/html/article?%s=abc#section', RtmClickReplace::HASH_PARAM));
        $this->assertEquals('abc', $hash);

        $hash = $this->rtmClickReplace->getRtmClickHashFromUrl(sprintf('https://expresso.pt/html/article?id=1&%s=abc#section', RtmClickReplace::HASH_PARAM));
        $this->assertEquals('abc', $hash);
    }

    public static function setRtmClickHashInFragmentUrlProvider(): array
    {
        return [
            'FragmentOnly' => [
                'input' => 'https://expresso.pt/html/article#section',
                'hash' => 'H',
                'expected' => 'https://expresso.pt/html/article?rtm_click=H#section',
            ],
            'QueryAndFragment' => [
                'input' => 'https://expresso.pt/html/article?id=1#section',
                'hash' => 'H',
                'expected' => 'https://expresso.pt/html/article?id=1&rtm_click=H#section',
            ],
            'ReplacesExistingRtmClickWithFragment' => [
                'input' => 'https://expresso.pt/html/article?rtm_click=OLD#section',
                'hash' => 'H',
                'expected' => 'https://expresso.pt/html/article?rtm_click=H#section',
            ],
            'QuestionMarkInsideFragment' => [
                'input' => 'https://expresso.pt/html/article#a?b=1',
                'hash' => 'H',
                'expected' => 'https://expresso.pt/html/article?rtm_click=H#a?b=1',
            ],
        ];
    }

    #[DataProvider('setRtmClickHashInFragmentUrlProvider')]
    public function testFragmentUrlsKeepRtmClickInQuery(string $input, string $hash, string $expected): void
    {
        $produced = $this->rtmClickReplace->setRtmClickHashInUrl($input, $hash);
        $this->assertEquals($expected, $produced);
        $this->assertEquals($hash, (new Url($produced))->getQueryParameter(RtmClickReplace::HASH_PARAM));
    }

    public static function removeRtmClickHashFragmentProvider(): array
    {
        return [
            'RtmClickAndFragment' => [
                'input' => 'https://expresso.pt/html/article?rtm_click=1234#section',
                'expected' => 'https://expresso.pt/html/article#section',
            ],
            'QueryRtmClickAndFragment' => [
                'input' => 'https://expresso.pt/html/article?id=1&rtm_click=1234#section',
                'expected' => 'https://expresso.pt/html/article?id=1#section',
            ],
            'FragmentOnlyNoRtmClick' => [
                'input' => 'https://expresso.pt/html/article#section',
                'expected' => 'https://expresso.pt/html/article#section',
            ],
        ];
    }

    #[DataProvider('removeRtmClickHashFragmentProvider')]
    public function testRemoveRtmClickHashPreservesFragment(string $input, string $expected): void
    {
        $this->assertEquals($expected, RtmClickReplace::removeRtmClickHash($input));
    }
}
