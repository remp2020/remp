<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Nette\Utils\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\MailerModule\Api\v1\Handlers\Users\IsSubscribedHandler;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class IsSubscribedHandlerTest extends BaseApiHandlerTestCase
{
    #[DataProvider('dataProvider')]
    public function testIsSubscribed($testParams, $isSubscribed)
    {
        // Prepare data
        $userId = 123;
        $email = 'example@example.com';
        $mailType1 = $this->createMailTypeWithCategory(categoryName: "category1", typeCode: 'code1');
        $mailType2 = $this->createMailTypeWithCategory(categoryName: "category1", typeCode: 'code2');
        $mailType3 = $this->createMailTypeWithCategory(categoryName: "category1", typeCode: 'code3');
        $mailType4 = $this->createMailTypeWithCategory(categoryName: "category1", typeCode: 'code4');
        $variant1_1 = $this->createMailTypeVariant(mailType: $mailType1, code: 'variant1_1');
        $variant1_2 = $this->createMailTypeVariant(mailType: $mailType1, code: 'variant1_2');

        $this->createMailUserSubscription(mailType: $mailType1, userID: $userId, email: $email, variantID: $variant1_1->id);
        $this->createMailUserSubscription(mailType: $mailType2, userID: $userId, email: $email);
        $this->createMailUserSubscription(mailType: $mailType3, userID: $userId, email: $email);
        $this->userSubscriptionsRepository->unsubscribeUser($mailType3, $userId, $email);

        // Prepare params - data provider doesn't know IDs directly, get from code
        $variantsIds = [
            $variant1_1->code => $variant1_1->id,
            $variant1_2->code => $variant1_2->id,
        ];
        $mailTypesIds = [
            $mailType1->code => $mailType1->id,
            $mailType2->code => $mailType2->id,
            $mailType3->code => $mailType3->id,
            $mailType4->code => $mailType4->id,
        ];
        $params = array_filter([
            "list_id" => $mailTypesIds[$testParams['list_code']],
            "variant_id" => isset($testParams['variant_code']) ? $variantsIds[$testParams['variant_code']] : null,
            "user_id" => $userId,
            "email" => $email,
        ]);

        // Test
        /** @var IsSubscribedHandler $handler */
        $handler = $this->getHandler(IsSubscribedHandler::class);
        $response = $handler->handle(['raw' => Json::encode($params)]);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(IResponse::S200_OK, $response->getCode());
        $payload = $response->getPayload();

        $this->assertEquals($isSubscribed, $payload['is_subscribed']);
    }

    public static function dataProvider(): array
    {
        return [
            [
                'testParams' => ['list_code' => 'code1', 'variant_code' => 'variant1_1'],
                'isSubscribed' => true,
            ],
            [
                'testParams' => ['list_code' => 'code1'],
                'isSubscribed' => true,
            ],
            [
                'testParams' => ['list_code' => 'code1', 'variant_code' => 'variant1_2'],
                'isSubscribed' => false,
            ],
            [
                'testParams' => ['list_code' => 'code2'],
                'isSubscribed' => true,
            ],
            [
                'testParams' => ['list_code' => 'code3'],
                'isSubscribed' => false,
            ],
            [
                'testParams' => ['list_code' => 'code4'],
                'isSubscribed' => false,
            ],
        ];
    }
}
