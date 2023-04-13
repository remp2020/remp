<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v3\Mailers;

use Remp\MailerModule\Api\v3\Handlers\Mailers\MailTypesListingHandler;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailTypesListingHandlerTest extends BaseApiHandlerTestCase
{
    /** @var MailTypesListingHandler */
    private $handler;

    public function setUp(): void
    {
        parent::setUp();
        $this->handler = $this->getHandler(MailTypesListingHandler::class);
    }

    public function testEmptyList()
    {
        $params = [];
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertCount(0, $response->getPayload()['data']);
    }

    public function testListPublic()
    {
        $this->createMailTypes();

        $params = ['public_listing' => 1];
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertCount(1, $response->getPayload()['data']);
    }

    public function testListByCode()
    {
        $this->createMailTypes();

        $params = ['code' => 'code2'];
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertCount(1, $response->getPayload()['data']);
    }

    public function testListByUnknownCode()
    {
        $this->createMailTypes();

        $params = ['code' => 'codeZ'];
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertCount(0, $response->getPayload()['data']);
    }

    public function testListByCategoryCode()
    {
        $this->createMailTypes();

        $params = ['mail_type_category_code' => 'category2'];
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertCount(1, $response->getPayload()['data']);
    }

    public function testListPublicByCategoryCode()
    {
        $this->createMailTypes();

        $params = ['mail_type_category_code' => 'category2', 'public_listing' => 1];
        $response =  $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertCount(0, $response->getPayload()['data']);
    }

    public function testListWithVariants()
    {
        $mailType = $this->createMailTypeWithCategory("category1", "code1", "name1");

        $mailTypeVariant1 = $this->createMailTypeVariant($mailType, 'test1');
        $mailTypeVariant2 = $this->createMailTypeVariant($mailType, 'test2');

        $params = [];
        $response =  $this->handler->handle($params);

        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertCount(2, $response->getPayload()['data'][0]->variants);

        $this->listVariantsRepository->softDelete($mailTypeVariant1);

        /** @var JsonApiResponse $response */
        $response =  $this->handler->handle($params);

        $data = $response->getPayload()['data'];
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertCount(1, $data[0]->variants);
        $this->assertEquals($mailTypeVariant2->title, $data[0]->variants[$mailTypeVariant2->id]->title);
        $this->assertEquals($mailTypeVariant2->code, $data[0]->variants[$mailTypeVariant2->id]->code);
    }

    private function createMailTypes()
    {
        $this->createMailTypeWithCategory("category1", "code1", "name1", true);
        $this->createMailTypeWithCategory("category1", "code2", "name2", false);
        $this->createMailTypeWithCategory("category2", "code3", "name3", false);
    }
}
