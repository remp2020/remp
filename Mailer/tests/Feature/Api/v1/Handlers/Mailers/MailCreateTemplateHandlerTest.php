<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Mailers;

use Remp\MailerModule\Api\v1\Handlers\Mailers\MailCreateTemplateHandler;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailCreateTemplateHandlerTest extends BaseApiHandlerTestCase
{
    /** @var MailCreateTemplateHandler */
    private $handler;

    public function setUp(): void
    {
        parent::setUp();
        $this->handler = $this->getHandler(MailCreateTemplateHandler::class);
    }

    public function testApiValidParamsShouldCreateNewTemplate()
    {
        $params = $this->getDefaultParams([
            'code' => 'foo',
        ]);

        /** @var JsonApiResponse $response */
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('foo', $response->getPayload()['code']);
    }

    public function testClickTrackingNoParamShouldUseDefault()
    {
        $params = $this->getDefaultParams([
            'click_tracking' => null,
        ]);

        /** @var JsonApiResponse $response */
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());

        $template = $this->templatesRepository->findBy('code', $response->getPayload()['code']);
        $this->assertNull($template->click_tracking);
    }

    public function testClickTrackingWithTruthyParamShouldStoreTrue()
    {
        $params = $this->getDefaultParams([
            'click_tracking' => 1,
        ]);

        /** @var JsonApiResponse $response */
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());

        $template = $this->templatesRepository->findBy('code', $response->getPayload()['code']);
        $this->assertEquals(1, $template->click_tracking);
    }

    public function testClickTrackingWithFalsyParamShouldStoreTrue()
    {
        $params = $this->getDefaultParams([
            'click_tracking' => 'off',
        ]);

        /** @var JsonApiResponse $response */
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());

        $template = $this->templatesRepository->findBy('code', $response->getPayload()['code']);
        $this->assertEquals(0, $template->click_tracking);
    }

    private function getDefaultParams($params)
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1",
            true
        );
        $mailLayout = $this->createMailLayout();

        return array_merge([
            'name' => 'test_name',
            'code' => 'test_code',
            'description' => 'test_description',
            'mail_layout_id' => $mailLayout->id,
            'mail_type_code' => $mailType->code,
            'from' => 'ADMIN <admin@example.com>',
            'subject' => 'Test email subject',
            'template_text' => 'email content',
            'template_html' => '<strong>email content</strong>',
        ], $params);
    }
}
