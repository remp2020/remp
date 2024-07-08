<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Mailers;

use Nette\Utils\Random;
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
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('foo', $response->getPayload()['code']);
    }

    public function testApiValidParamsWithLayoutCodeShouldCreateNewTemplate()
    {
        $params = $this->getDefaultParams([
            'mail_layout_id' => null,
            'mail_layout_code' => 'layout',
        ]);

        /** @var JsonApiResponse $response */
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());

        $template = $this->templatesRepository->findBy('code', $response->getPayload()['code']);
        $this->assertEquals('layout', $template->mail_layout->code);
    }

    public function testApiWithNameTooLongShouldReturnBadRequest()
    {
        $name = Random::generate(MailCreateTemplateHandler::NAME_MAX_LENGTH + 1);

        $params = $this->getDefaultParams([
            'name' => $name,
        ]);

        /** @var JsonApiResponse $response */
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(400, $response->getCode());
        $this->assertEquals('name_too_long', $response->getPayload()['code']);
    }

    public function testApiWithoutLayoutIdOrCoudShouldReturnNotFound()
    {
        $params = $this->getDefaultParams([
            'mail_layout_id' => null,
            'mail_layout_code' => 'null',
        ]);

        /** @var JsonApiResponse $response */
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(404, $response->getCode());
        $this->assertEquals('mail_layout_not_found', $response->getPayload()['code']);
    }

    public function testClickTrackingNoParamShouldUseDefault()
    {
        $params = $this->getDefaultParams([
            'click_tracking' => null,
        ]);

        /** @var JsonApiResponse $response */
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
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
        $this->assertInstanceOf(JsonApiResponse::class, $response);
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
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());

        $template = $this->templatesRepository->findBy('code', $response->getPayload()['code']);
        $this->assertEquals(0, $template->click_tracking);
    }

    private function getDefaultParams($params)
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
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
