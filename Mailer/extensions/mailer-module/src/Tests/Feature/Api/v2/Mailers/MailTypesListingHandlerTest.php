<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v2\Mailers;

use Remp\MailerModule\Api\v2\Handlers\Mailers\MailTypesListingHandler;
use Tests\Feature\Api\BaseApiHandlerTestCase;

class MailTypesListingHandlerTest extends BaseApiHandlerTestCase
{
    public function testEmptyList()
    {
        $params = [];
        $handler = $this->getHandler(MailTypesListingHandler::class);
        $response =  $handler->handle($params);
        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertCount(0, $response->getPayload()['data']);
    }

    public function testListPublic()
    {
        $this->createMailTypes();

        $params = ['public_listing' => 1];
        $handler = $this->getHandler(MailTypesListingHandler::class);
        $response =  $handler->handle($params);
        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertCount(1, $response->getPayload()['data']);
    }

    public function testListByCode()
    {
        $this->createMailTypes();

        $params = ['code' => 'code2'];
        $handler = $this->getHandler(MailTypesListingHandler::class);
        $response =  $handler->handle($params);
        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertCount(1, $response->getPayload()['data']);
    }

    public function testListByUnknownCode()
    {
        $this->createMailTypes();

        $params = ['code' => 'codeZ'];
        $handler = $this->getHandler(MailTypesListingHandler::class);
        $response =  $handler->handle($params);
        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertCount(0, $response->getPayload()['data']);
    }

    public function testListByCategoryCode()
    {
        $this->createMailTypes();

        $params = ['mail_type_category_code' => 'category2'];
        $handler = $this->getHandler(MailTypesListingHandler::class);
        $response =  $handler->handle($params);
        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertCount(1, $response->getPayload()['data']);
    }

    public function testListPublicByCategoryCode()
    {
        $this->createMailTypes();

        $params = ['mail_type_category_code' => 'category2', 'public_listing' => 1];
        $handler = $this->getHandler(MailTypesListingHandler::class);
        $response =  $handler->handle($params);
        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertCount(0, $response->getPayload()['data']);
    }

    public function testListWithVariants()
    {
        $mailType = $this->createMailTypeWithCategory("category1", "code1", "name1");

        $mailTypeVariant1 = $this->createMailTypeVariant($mailType, 'test1');
        $mailTypeVariant2 = $this->createMailTypeVariant($mailType, 'test2');

        $params = [];
        $handler = $this->getHandler(\Remp\MailerModule\Api\v1\Handlers\Mailers\MailTypesListingHandler::class);
        $response =  $handler->handle($params);

        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertCount(2, $response->getPayload()['data'][0]->variants);

        $this->listVariantsRepository->softDelete($mailTypeVariant1);

        $handler = $this->getHandler(MailTypesListingHandler::class);
        /** @var \Tomaj\NetteApi\Response\JsonApiResponse $response */
        $response =  $handler->handle($params);

        $data = $response->getPayload()['data'];

        $this->assertInstanceOf(\Tomaj\NetteApi\Response\JsonApiResponse::class, $response);
        $this->assertCount(1, $data[0]->variants);
        $this->assertEquals([$mailTypeVariant2->id => $mailTypeVariant2->title], $data[0]->variants);
    }

    private function createMailTypes()
    {
        $this->createMailTypeWithCategory("category1", "code1", "name1", true);
        $this->createMailTypeWithCategory("category1", "code2", "name2", false);
        $this->createMailTypeWithCategory("category2", "code3", "name3", false);
    }
}
