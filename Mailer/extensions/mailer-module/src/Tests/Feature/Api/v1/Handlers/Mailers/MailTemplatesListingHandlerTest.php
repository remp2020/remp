<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Mailers;

use PHPUnit\Framework\Attributes\DataProvider;
use Remp\MailerModule\Api\v1\Handlers\Mailers\MailTemplatesListingHandler;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class MailTemplatesListingHandlerTest extends BaseApiHandlerTestCase
{
    public function testValidPageAndLimit()
    {
        $layout = $this->createMailLayout();
        $mailType = $this->createMailTypeWithCategory('test_category', 'test_mail_type');

        foreach (range(1, 10) as $i) {
            $this->createTemplate($layout, $mailType, 'template_' . $i);
        }

        $handler = $this->getHandler(MailTemplatesListingHandler::class);

        // get 2 latest templates

        $params = ['limit' => 2, 'page' => 1];
        $response =  $handler->handle($params);

        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertCount(2, $response->getPayload());
        $this->assertEquals('template_10', $response->getPayload()[0]['code']);
        $this->assertEquals('template_9', $response->getPayload()[1]['code']);

        $params = ['limit' => 2, 'page' => 3];
        $response =  $handler->handle($params);

        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertCount(2, $response->getPayload());
        $this->assertEquals('template_6', $response->getPayload()[0]['code']);
        $this->assertEquals('template_5', $response->getPayload()[1]['code']);
    }

    #[DataProvider('badRequestDataProvider')]
    public function testBadRequest($params, $error)
    {
        $handler = $this->getHandler(MailTemplatesListingHandler::class);
        $response = $handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals($error, $response->getPayload()['code']);
    }

    public static function badRequestDataProvider()
    {
        return [
            'PageLimit_MissingPage' => [
                'params' => ['limit' => 10],
                'error' => 'invalid_pagination_params',
            ],
            'PageLimit_ZeroPage' => [
                'params' => ['limit' => 10, 'page' => 0],
                'error' => 'invalid_pagination_params',
            ],
            'PageLimit_NegativePage' => [
                'params' => ['limit' => 10, 'page' => -5],
                'error' => 'invalid_pagination_params',
            ],
            'PageLimit_StringPage' => [
                'params' => ['limit' => 10, 'page' => 'foo'],
                'error' => 'invalid_pagination_params',
            ],
        ];
    }
}
