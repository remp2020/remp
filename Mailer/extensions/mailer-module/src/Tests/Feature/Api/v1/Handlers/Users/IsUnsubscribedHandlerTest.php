<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Nette\Utils\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use Remp\MailerModule\Api\v1\Handlers\Users\IsUnsubscribedHandler;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class IsUnsubscribedHandlerTest extends BaseApiHandlerTestCase
{
    #[DataProvider('dataProvider')]
    public function testIsUnsubscribed($testParams, $isUnsubscribed)
    {
        // Prepare data
        $userId = 123;
        $email = 'example@example.com';
        $mailType1 = $this->createMailTypeWithCategory(categoryName: "category1", typeCode: 'code1');
        $mailType2 = $this->createMailTypeWithCategory(categoryName: "category1", typeCode: 'code2');
        $mailType3 = $this->createMailTypeWithCategory(categoryName: "category1", typeCode: 'code3');

        $this->createMailUserSubscription(mailType: $mailType1, userID: $userId, email: $email);
        $this->createMailUserSubscription(mailType: $mailType2, userID: $userId, email: $email);
        $this->userSubscriptionsRepository->unsubscribeUser($mailType2, $userId, $email);

        // Prepare params - data provider doesn't know IDs directly, get from code
        $mailTypesIds = [
            $mailType1->code => $mailType1->id,
            $mailType2->code => $mailType2->id,
            $mailType3->code => $mailType3->id,
        ];
        $params = array_filter([
            "list_id" => $mailTypesIds[$testParams['list_code']],
            "user_id" => $userId,
            "email" => $email,
        ]);

        // Test
        /** @var IsUnsubscribedHandler $handler */
        $handler = $this->getHandler(IsUnsubscribedHandler::class);
        $response = $handler->handle(['raw' => Json::encode($params)]);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(IResponse::S200_OK, $response->getCode());
        $payload = $response->getPayload();

        $this->assertEquals($isUnsubscribed, $payload['is_unsubscribed']);
    }

    public static function dataProvider(): array
    {
        return [
            [
                'testParams' => ['list_code' => 'code1'],
                'isUnsubscribed' => false,
            ],
            [
                'testParams' => ['list_code' => 'code2'],
                'isUnsubscribed' => true,
            ],
            // if no record is present in mail_user_subscriptions, return 'false' (user is not explicitly unsubscribed)
            [
                'testParams' => ['list_code' => 'code3'],
                'isUnsubscribed' => false,
            ],
        ];
    }
}
