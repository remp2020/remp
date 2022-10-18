<?php

namespace Tests\Feature\Api\v1\Handlers\Mailers;

use Nette\Utils\Json;
use Remp\MailerModule\Api\v1\Handlers\Mailers\MailTypeVariantCreateApiHandler;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailTypeVariantCreateApiHandlerTest extends BaseApiHandlerTestCase
{
    /** @var MailTypeVariantCreateApiHandler */
    private $handler;

    public function setUp(): void
    {
        parent::setUp();
        $this->handler = $this->getHandler(MailTypeVariantCreateApiHandler::class);
    }

    public function testValidParamsShouldCreateNewMailTypeVariant()
    {
        $params = $this->getDefaultParams();

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $payload = $response->getPayload();
        $params = Json::decode($params['raw']);

        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('ok', $payload['status']);

        $this->assertIsNumeric($payload['id']);
        $this->assertEquals($params->mail_type_code, $payload['mail_type_code']);
        $this->assertEquals($params->title, $payload['title']);
        $this->assertEquals($params->code, $payload['code']);
        $this->assertEquals($params->sorting, $payload['sorting']);

        $mailTypeVariant = $this->listVariantsRepository->find($payload['id']);
        $this->assertEquals($params->mail_type_code, $mailTypeVariant->mail_type->code);
        $this->assertEquals($params->title, $mailTypeVariant->title);
        $this->assertEquals($params->code, $mailTypeVariant->code);
        $this->assertEquals($params->sorting, $mailTypeVariant->sorting);
    }

    public function testValidOnlyRequiredParamsShouldCreateNewMailTypeVariant()
    {
        $params = $this->getDefaultParams(sorting: null);

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $payload = $response->getPayload();
        $params = Json::decode($params['raw']);

        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('ok', $payload['status']);

        $this->assertIsNumeric($payload['id']);
        $this->assertEquals($params->mail_type_code, $payload['mail_type_code']);
        $this->assertEquals($params->title, $payload['title']);
        $this->assertEquals($params->code, $payload['code']);
        $this->assertIsNumeric($payload['sorting']);

        $mailTypeVariant = $this->listVariantsRepository->find($payload['id']);
        $this->assertEquals($params->mail_type_code, $mailTypeVariant->mail_type->code);
        $this->assertEquals($params->title, $mailTypeVariant->title);
        $this->assertEquals($params->code, $mailTypeVariant->code);
        $this->assertIsNumeric($mailTypeVariant->sorting);
    }

    public function testInvalidMailTypeCode()
    {
        $params = $this->getDefaultParams('invalid-code');

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $payload = $response->getPayload();

        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(404, $response->getCode());
        $this->assertEquals('error', $payload['status']);
    }

    public function testMailTypeVariantAlreadyExists()
    {
        $params = $this->getDefaultParams();

        // creates mail type variant
        $this->handler->handle($params);

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $payload = $response->getPayload();

        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(400, $response->getCode());
        $this->assertEquals('error', $payload['status']);
    }

    private function getDefaultParams($mailTypeCode = null, $sorting = 100)
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );

        $payload = [
            'mail_type_code' => $mailTypeCode ?? $mailType->code,
            'title' => 'Title',
            'code' => 'code',
        ];

        if (isset($sorting)) {
            $payload['sorting'] = $sorting;
        }

        return [
            'raw' => Json::encode($payload)
        ];
    }
}
