<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Nette\Utils\Json;
use Remp\MailerModule\Api\v1\Handlers\Users\SubscribeHandler;
use Remp\MailerModule\Api\v1\Handlers\Users\UserDeleteApiHandler;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionVariantsRepository;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class SubscribeHandlerTest extends BaseApiHandlerTestCase
{
    /** @var UserDeleteApiHandler */
    private $handler;

    public function setUp(): void
    {
        parent::setUp();
        $this->handler = $this->getHandler(SubscribeHandler::class);
    }

    public function testSuccessfulSubscribeWithID()
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
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

        /** @var UserSubscriptionsRepository $userSubscriptionsRepository */
        $userSubscriptionsRepository = $this->inject(UserSubscriptionsRepository::class);
        $isSubscribed = (bool) $userSubscriptionsRepository->getTable()->where([
            'user_id' => 123,
            'user_email' => 'example@example.com',
            'mail_type_id' => $mailType->id,
            'subscribed' => 1,
        ])->count('*');
        $this->assertTrue($isSubscribed);
    }

    public function testSuccessfulSubscribeWithCode()
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
                'list_code' => $mailType->code,
            ])
        ];

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

        /** @var UserSubscriptionsRepository $userSubscriptionsRepository */
        $userSubscriptionsRepository = $this->inject(UserSubscriptionsRepository::class);
        $isSubscribed = (bool) $userSubscriptionsRepository->getTable()->where([
            'user_id' => 123,
            'user_email' => 'example@example.com',
            'mail_type_id' => $mailType->id,
            'subscribed' => 1,
        ])->count('*');
        $this->assertTrue($isSubscribed);
    }

    public function testSuccessfulSubscribeWithVariantId()
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );
        $variant = $this->createMailTypeVariant($mailType, 'Foo', 'foo');

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
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

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

    public function testSuccessfulSubscribeWithVariantCode()
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );
        $variant = $this->createMailTypeVariant($mailType, 'Foo', 'foo');

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
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

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

    public function testFailingSubscribeWithInvalidVariantCode()
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );
        $variant = $this->createMailTypeVariant($mailType, 'Foo', 'foo');

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
        $this->assertEquals(IResponse::S404_NotFound, $response->getCode());

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
}
