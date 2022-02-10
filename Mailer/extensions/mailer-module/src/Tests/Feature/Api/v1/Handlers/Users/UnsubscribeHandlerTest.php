<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Users;

use Nette\Http\Response;
use Nette\Utils\Json;
use Remp\MailerModule\Api\v1\Handlers\Users\UnSubscribeHandler;
use Remp\MailerModule\Api\v1\Handlers\Users\UserDeleteApiHandler;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionVariantsRepository;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class UnsubscribeHandlerTest extends BaseApiHandlerTestCase
{
    /** @var UserDeleteApiHandler */
    private $handler;

    public function setUp(): void
    {
        parent::setUp();
        $this->handler = $this->getHandler(UnSubscribeHandler::class);
    }

    public function testSuccessfulUnsubscribeWithID()
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );

        $params = [
            'raw' => Json::encode([
                'user_id' => 123,
                'email' => 'example@example.com',
                'list_id' => $mailType->id,
            ])
        ];

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(Response::S200_OK, $response->getCode());

        /** @var UserSubscriptionsRepository $userSubscriptionsRepository */
        $userSubscriptionsRepository = $this->inject(UserSubscriptionsRepository::class);
        $isSubscribed = (bool) $userSubscriptionsRepository->getTable()->where([
            'user_id' => 123,
            'user_email' => 'example@example.com',
            'mail_type_id' => $mailType->id,
            'subscribed' => 1,
        ])->count('*');
        $this->assertFalse($isSubscribed);
    }

    public function testSuccessfulUnsubscribeWithCode()
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );
        $this->createMailUserSubscription($mailType, 123, 'example@example.com');

        $params = [
            'raw' => Json::encode([
                'user_id' => 123,
                'email' => 'example@example.com',
                'list_code' => $mailType->code,
            ])
        ];

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(Response::S200_OK, $response->getCode());

        /** @var UserSubscriptionsRepository $userSubscriptionsRepository */
        $userSubscriptionsRepository = $this->inject(UserSubscriptionsRepository::class);
        $isSubscribed = (bool) $userSubscriptionsRepository->getTable()->where([
            'user_id' => 123,
            'user_email' => 'example@example.com',
            'mail_type_id' => $mailType->id,
            'subscribed' => 1,
        ])->count('*');
        $this->assertFalse($isSubscribed);
    }

    public function testSuccessfulUnsubscribeWithVariantId()
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );
        $variant = $this->createMailTypeVariant($mailType, 'Foo', 'foo');
        $this->createMailUserSubscription($mailType, 123, 'example@example.com', $variant->id);

        $params = [
            'raw' => Json::encode([
                'user_id' => 123,
                'email' => 'example@example.com',
                'list_code' => $mailType->code,
                'variant_id' => $variant->id,
            ])
        ];

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(Response::S200_OK, $response->getCode());

        /** @var UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository */
        $userSubscriptionVariantsRepository = $this->inject(UserSubscriptionVariantsRepository::class);
        $isSubscribed = (bool) $userSubscriptionVariantsRepository->getTable()->where([
            'mail_user_subscription.user_id' => 123,
            'mail_user_subscription.user_email' => 'example@example.com',
            'mail_user_subscription.subscribed' => 1,
            'mail_type_variant_id' => $variant->id,
        ])->count('*');
        $this->assertFalse($isSubscribed);
    }

    public function testSuccessfulUnsubscribeWithVariantCode()
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );
        $variant = $this->createMailTypeVariant($mailType, 'Foo', 'foo');
        $this->createMailUserSubscription($mailType, 123, 'example@example.com', $variant->id);

        $params = [
            'raw' => Json::encode([
                'user_id' => 123,
                'email' => 'example@example.com',
                'list_code' => $mailType->code,
                'variant_code' => $variant->code,
            ])
        ];

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(Response::S200_OK, $response->getCode());

        /** @var UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository */
        $userSubscriptionVariantsRepository = $this->inject(UserSubscriptionVariantsRepository::class);
        $isSubscribed = (bool) $userSubscriptionVariantsRepository->getTable()->where([
            'mail_user_subscription.user_id' => 123,
            'mail_user_subscription.user_email' => 'example@example.com',
            'mail_user_subscription.subscribed' => 1,
            'mail_type_variant_id' => $variant->id,
        ])->count('*');
        $this->assertFalse($isSubscribed);
    }

    public function testFailingSubscribeWithInvalidVariantCode()
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );
        $variant = $this->createMailTypeVariant($mailType, 'Foo', 'foo');
        $this->createMailUserSubscription($mailType, 123, 'example@example.com', $variant->id);

        $params = [
            'raw' => Json::encode([
                'user_id' => 123,
                'email' => 'example@example.com',
                'list_code' => $mailType->code,
                'variant_code' => 'code_wrong',
            ])
        ];

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(Response::S404_NOT_FOUND, $response->getCode());

        /** @var UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository */
        $userSubscriptionVariantsRepository = $this->inject(UserSubscriptionVariantsRepository::class);
        $isSubscribed = (bool) $userSubscriptionVariantsRepository->getTable()->where([
            'mail_user_subscription.user_id' => 123,
            'mail_user_subscription.user_email' => 'example@example.com',
            'mail_user_subscription.subscribed' => 1,
            'mail_type_variant_id' => $variant->id,
        ])->count('*');
        $this->assertTrue($isSubscribed);
    }
}
