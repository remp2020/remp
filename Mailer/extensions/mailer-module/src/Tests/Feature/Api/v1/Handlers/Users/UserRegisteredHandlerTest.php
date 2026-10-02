<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Remp\MailerModule\Api\v1\Handlers\Users\UserRegisteredHandler;
use Tests\Feature\Api\BaseApiHandlerTestCase;

class UserRegisteredHandlerTest extends BaseApiHandlerTestCase
{
    public function testExternalListsAreSkipped()
    {
        $internalMailType = $this->createMailTypeWithCategory(typeCode: 'internal');
        $externalMailType = $this->createMailTypeWithCategory(typeCode: 'external', isExternal: true);

        /** @var UserRegisteredHandler $handler */
        $handler = $this->getHandler(UserRegisteredHandler::class);
        $response = $handler->handle(['email' => 'example@example.com', 'user_id' => '123']);
        $this->assertEquals(IResponse::S200_OK, $response->getCode());

        $this->assertNotNull($this->userSubscriptionsRepository->getEmailSubscription($internalMailType, 'example@example.com'));
        $this->assertNull($this->userSubscriptionsRepository->getEmailSubscription($externalMailType, 'example@example.com'));
    }
}
