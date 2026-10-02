<?php
declare(strict_types=1);

namespace Tests\Unit\ContentGenerator\Replace;

use Nette\DI\Container;
use PHPUnit\Framework\TestCase;
use Remp\Mailer\Models\Realic\RealicUrlSigner;
use Remp\MailerModule\Models\ContentGenerator\AllowedDomainManager;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Models\ContentGenerator\Replace\ImageUrlSignReplace;
use Remp\MailerModule\Models\ContentGenerator\Replace\TextUrlRtmReplace;
use Remp\MailerModule\Models\ContentGenerator\Replace\UrlRtmReplace;
use Remp\MailerModule\Models\MailTranslator;
use Remp\MailerModule\Repositories\ActiveRowFactory;

class ImageUrlSignReplaceTest extends TestCase
{
    private const KEY = 'secret';

    private ContentGenerator $contentGenerator;
    private GeneratorInput $generatorInput;

    protected function setUp(): void
    {
        /** @var Container $container */
        $container = $GLOBALS['container'];
        $activeRowFactory = $container->getByType(ActiveRowFactory::class);

        $mailTemplate = $activeRowFactory->create([
            'code' => 'daily_newsletter',
            'mail_type' => $activeRowFactory->create(['code' => 'newsletter']),
        ]);
        $this->generatorInput = $container->getByType(GeneratorInputFactory::class)
            ->create($mailTemplate, [], 123);

        $allowedDomainManager = new AllowedDomainManager();
        $allowedDomainManager->addDomain('dennikn.sk');

        $this->contentGenerator = new ContentGenerator(new EngineFactory(), $this->createStub(MailTranslator::class));
        // registered first on purpose, the priority alone has to put it after the RTM replacers
        $this->contentGenerator->register(
            replace: new ImageUrlSignReplace(new RealicUrlSigner(self::KEY, 'img.dennikn.sk', ['img.projektn.sk'])),
            priority: 1000, // lower than the lowest
        );
        $this->contentGenerator->register(new UrlRtmReplace($allowedDomainManager));
        $this->contentGenerator->register(new TextUrlRtmReplace($allowedDomainManager), ContentGenerator::PRIORITY_LOW);
    }

    public function testSignatureCoversRtmParamsOfImageParam(): void
    {
        $params = $this->contentGenerator->getEmailParams($this->generatorInput, [
            'article_1_image' => 'https://img.dennikn.sk/wp-static/2026/10/a.jpg?w=558&h=270&fit=crop',
            'article_1_title' => 'Title',
        ]);

        $this->assertSame('Title', $params['article_1_title']);
        $this->assertStringStartsWith(
            'https://img.dennikn.sk/wp-static/2026/10/a.jpg?w=558&h=270&fit=crop'
                . '&rtm_source=newsletter&rtm_medium=email&rtm_campaign=daily_newsletter&rtm_content=123&s=',
            $params['article_1_image'],
        );
        $this->assertValidSignature($params['article_1_image']);
    }

    public function testSignatureCoversRtmParamsOfImageUrlInTextContent(): void
    {
        $params = $this->contentGenerator->getEmailParams($this->generatorInput, [
            'text' => "See https://img.dennikn.sk/wp-static/2026/10/a.jpg?w=558 for the chart.\n",
        ]);

        preg_match('~https://\S+~', $params['text'], $matches);
        $this->assertStringContainsString('rtm_content=123', $matches[0]);
        $this->assertValidSignature($matches[0]);
        $this->assertStringEndsWith(" for the chart.\n", $params['text']);
    }

    public function testNonImageUrlIsNotSigned(): void
    {
        $params = $this->contentGenerator->getEmailParams($this->generatorInput, [
            'article_1_href_url' => 'https://dennikn.sk/123/article/',
        ]);

        $this->assertStringNotContainsString('s=', $params['article_1_href_url']);
    }

    /**
     * Recomputes the signature the way Glide (and so realic) verifies it.
     */
    private function assertValidSignature(string $url): void
    {
        $parts = parse_url($url);
        parse_str($parts['query'], $params);
        $signature = $params['s'];
        unset($params['s']);
        ksort($params);

        $this->assertSame(
            // @phpstan-ignore disallowed.function (md5 is Glide's signature algorithm)
            md5(self::KEY . ':' . ltrim(rawurldecode($parts['path']), '/') . '?' . http_build_query($params)),
            $signature,
        );
    }
}
