<?php
declare(strict_types=1);

namespace Tests\Feature\Hermes;

use Psr\Log\NullLogger;
use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Hermes\ListCreatedHandler;
use Remp\MailerModule\Models\Users\IUser;
use Tests\Feature\BaseFeatureTestCase;

class ListCreatedHandlerTest extends BaseFeatureTestCase
{
    private ListCreatedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $userProvider = new class implements IUser {
            public function list(array $userIds, int $page, bool $includeDeactivated = false): array
            {
                if ($page > 1) {
                    return [];
                }
                return [
                    1 => ['id' => 1, 'email' => 'user1@example.com'],
                    2 => ['id' => 2, 'email' => 'user2@example.com'],
                ];
            }
        };

        /** @var ListCreatedHandler $handler */
        $handler = $this->createInstance(ListCreatedHandler::class, ['userProvider' => $userProvider]);
        $handler->setLogger(new NullLogger());
        $this->handler = $handler;
    }

    public function testInternalListGetsUserSubscriptions()
    {
        $mailType = $this->createMailTypeWithCategory(typeCode: 'internal');

        $this->handler->handle(new HermesMessage('list-created', ['list_id' => $mailType->id]));

        $this->assertEquals(2, $this->userSubscriptionsRepository->getTable()->where('mail_type_id', $mailType->id)->count('*'));
    }

    public function testExternalListIsSkipped()
    {
        $mailType = $this->createMailTypeWithCategory(typeCode: 'external', isExternal: true);

        $this->handler->handle(new HermesMessage('list-created', ['list_id' => $mailType->id]));

        $this->assertEquals(0, $this->userSubscriptionsRepository->getTable()->where('mail_type_id', $mailType->id)->count('*'));
    }
}
