<?php
declare(strict_types=1);

namespace Feature\Api\v1\Handlers\Users;

use League\Event\EventDispatcher;
use Mockery;
use Nette\Http\IResponse;
use Remp\MailerModule\Api\v1\Handlers\Users\EmailChangedHandler;
use Remp\MailerModule\Events\BeforeUserEmailChangeEvent;
use Remp\MailerModule\Events\UserEmailChangedEvent;
use Remp\MailerModule\Repositories\ActiveRow;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;

class EmailChangedHandlerTest extends BaseApiHandlerTestCase
{
    public function testEmailChanged()
    {
        $userId = 123;
        $email = 'user@example.com';
        $mailType1 = $this->createMailTypeWithCategory(categoryName: "category1", typeCode: 'code1');
        $mailType2 = $this->createMailTypeWithCategory(categoryName: "category1", typeCode: 'code2');
        $mailType3 = $this->createMailTypeWithCategory(categoryName: "category1", typeCode: 'code3');

        $this->createMailUserSubscription(mailType: $mailType1, userID: $userId, email: $email);
        $this->createMailUserSubscription(mailType: $mailType2, userID: $userId, email: $email);
        $this->createMailUserSubscription(mailType: $mailType3, userID: $userId, email: $email);
        $this->userSubscriptionsRepository->unsubscribeUser($mailType3, $userId, $email);

        $beforeUserEmailChangeEventHandled = false;
        $userEmailChangedEventHandled = false;

        $beforeHandler = function (BeforeUserEmailChangeEvent $event) use (&$beforeUserEmailChangeEventHandled) {
            $this->assertSame('user@example.com', $event->originalEmail);
            $this->assertSame('shiny@example.com', $event->newEmail);
            $beforeUserEmailChangeEventHandled = true;
        };
        $afterHandler = function (UserEmailChangedEvent $event) use (&$userEmailChangedEventHandled) {
            $this->assertSame('user@example.com', $event->originalEmail);
            $this->assertSame('shiny@example.com', $event->newEmail);
            $userEmailChangedEventHandled = true;
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->inject(EventDispatcher::class);
        $dispatcher->subscribeTo(BeforeUserEmailChangeEvent::class, $beforeHandler);
        $dispatcher->subscribeTo(UserEmailChangedEvent::class, $afterHandler);

        /** @var EmailChangedHandler $handler */
        $handler = $this->getHandler(EmailChangedHandler::class);
        $response = $handler->handle([
            'original_email' => 'user@example.com',
            'new_email' => 'shiny@example.com'
        ]);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

        $this->assertFalse($this->isSubscribed('user@example.com', $mailType1));
        $this->assertFalse($this->isSubscribed('user@example.com', $mailType2));
        $this->assertFalse($this->isSubscribed('user@example.com', $mailType3));
        $this->assertTrue($this->isSubscribed('shiny@example.com', $mailType1));
        $this->assertTrue($this->isSubscribed('shiny@example.com', $mailType2));
        $this->assertFalse($this->isSubscribed('shiny@example.com', $mailType3));

        $this->assertTrue($beforeUserEmailChangeEventHandled);
        $this->assertTrue($userEmailChangedEventHandled);
    }

    public function testNoEmailData()
    {
        $beforeUserEmailChangeEventHandled = false;
        $userEmailChangedEventHandled = false;

        $beforeHandler = function (BeforeUserEmailChangeEvent $event) use (&$beforeUserEmailChangeEventHandled) {
            $this->assertSame('user@example.com', $event->originalEmail);
            $this->assertSame('shiny@example.com', $event->newEmail);
            $beforeUserEmailChangeEventHandled = true;
        };
        $afterHandler = function (UserEmailChangedEvent $event) use (&$userEmailChangedEventHandled) {
            $this->assertSame('user@example.com', $event->originalEmail);
            $this->assertSame('shiny@example.com', $event->newEmail);
            $userEmailChangedEventHandled = true;
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->inject(EventDispatcher::class);
        $dispatcher->subscribeTo(BeforeUserEmailChangeEvent::class, $beforeHandler);
        $dispatcher->subscribeTo(UserEmailChangedEvent::class, $afterHandler);

        /** @var EmailChangedHandler $handler */
        $handler = $this->getHandler(EmailChangedHandler::class);
        $response = $handler->handle([
            'original_email' => 'user@example.com',
            'new_email' => 'shiny@example.com'
        ]);
        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(IResponse::S404_NotFound, $response->getCode());

        $payload = $response->getPayload();
        $this->assertSame('no_subscription_found', $payload['code']);

        $this->assertTrue($beforeUserEmailChangeEventHandled);
        $this->assertFalse($userEmailChangedEventHandled);
    }

    private function isSubscribed(string $email, ActiveRow $mailType): bool
    {
        $subscription = $this->userSubscriptionsRepository->getEmailSubscription($mailType, $email);
        if (!$subscription) {
            return false;
        }

        return (bool) $subscription->subscribed;
    }
}
