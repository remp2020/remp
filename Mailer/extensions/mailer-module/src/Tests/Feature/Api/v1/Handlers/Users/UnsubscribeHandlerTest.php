<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Nette\Utils\Json;
use Remp\MailerModule\Api\v1\Handlers\Users\UnSubscribeHandler;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class UnsubscribeHandlerTest extends BaseApiHandlerTestCase
{
    /** @var UnSubscribeHandler */
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
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

        $isNotSubscribed = (bool) $this->userSubscriptionsRepository->getTable()->where([
            'user_id' => 123,
            'user_email' => 'example@example.com',
            'mail_type_id' => $mailType->id,
            'subscribed' => 0,
        ])->count('*');
        $this->assertTrue($isNotSubscribed);
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
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

        $isNotSubscribed = (bool) $this->userSubscriptionsRepository->getTable()->where([
            'user_id' => 123,
            'user_email' => 'example@example.com',
            'mail_type_id' => $mailType->id,
            'subscribed' => 0,
        ])->count('*');
        $this->assertTrue($isNotSubscribed);
    }

    public function testSuccessfulUnsubscribeWithVariantId()
    {
        $mailType = $this->createMailTypeWithCategory("category1", "code1", "name1");
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

        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

        $this->assertNull($this->userSubscriptionVariantsRepository->getTable()->where([
            'mail_user_subscription.user_id' => 123,
            'mail_user_subscription.user_email' => 'example@example.com',
            'mail_type_variant_id' => $variant->id,
        ])->fetch());

        $this->assertTrue($this->userSubscriptionsRepository->isUserUnsubscribed(123, $mailType->id));
    }

    public function testSuccessfulUnsubscribeWithVariantIdButKeepMailType()
    {
        $mailType = $this->createMailTypeWithCategory("category1", "code1", "name1");
        $variant = $this->createMailTypeVariant($mailType, 'Foo', 'foo');
        $this->createMailUserSubscription($mailType, 123, 'example@example.com', $variant->id);

        $params = [
            'raw' => Json::encode([
                'user_id' => 123,
                'email' => 'example@example.com',
                'list_code' => $mailType->code,
                'variant_id' => $variant->id,
                'keep_list_subscription' => true,
            ])
        ];

        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

        $this->assertNull($this->userSubscriptionVariantsRepository->getTable()->where([
            'mail_user_subscription.user_id' => 123,
            'mail_user_subscription.user_email' => 'example@example.com',
            'mail_type_variant_id' => $variant->id,
        ])->fetch());

        // user should still be kept in mail type subscription
        $this->assertFalse($this->userSubscriptionsRepository->isUserUnsubscribed(123, $mailType->id));
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
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

        $variantSubscription = $this->userSubscriptionVariantsRepository->getTable()->where([
            'mail_user_subscription.user_id' => 123,
            'mail_user_subscription.user_email' => 'example@example.com',
            'mail_type_variant_id' => $variant->id,
        ])->fetch();
        $this->assertNull($variantSubscription);
    }

    public function testSuccessfulUnsubscribeWithRtmParams()
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
                'rtm_params' => [
                    'rtm_source' => 'test',
                    'rtm_medium' => 'engine',
                    'rtm_campaign' => 'cmp',
                    'rtm_content' => 'code',
                ]
            ])
        ];

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

        $isNotSubscribed = (bool) $this->userSubscriptionsRepository->getTable()->where([
            'user_id' => 123,
            'user_email' => 'example@example.com',
            'mail_type_id' => $mailType->id,
            'subscribed' => 0,
            'rtm_source' => 'test',
            'rtm_medium' => 'engine',
            'rtm_campaign' => 'cmp',
            'rtm_content' => 'code',
        ])->count('*');
        $this->assertTrue($isNotSubscribed);
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
        $this->assertEquals(IResponse::S404_NotFound, $response->getCode());

        $isSubscribed = (bool) $this->userSubscriptionVariantsRepository->getTable()->where([
            'mail_user_subscription.user_id' => 123,
            'mail_user_subscription.user_email' => 'example@example.com',
            'mail_user_subscription.subscribed' => 1,
            'mail_type_variant_id' => $variant->id,
        ])->count('*');
        $this->assertTrue($isSubscribed);
    }
}
