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

    private function createMailTypes()
    {
        $this->createMailTypeWithCategory("category1", "code1", "name1", true);
        $this->createMailTypeWithCategory("category1", "code2", "name2", false);
        $this->createMailTypeWithCategory("category2", "code3", "name3", false);
    }
}
