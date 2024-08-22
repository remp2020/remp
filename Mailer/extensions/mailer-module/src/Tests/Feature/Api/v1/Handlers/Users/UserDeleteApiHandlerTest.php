<?php
declare(strict_types=1);

namespace Tests\Feature\Api\v1\Handlers\Users;

use Nette\Database\Table\ActiveRow;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Remp\MailerModule\Api\v1\Handlers\Users\UserDeleteApiHandler;
use Remp\MailerModule\Models\Auth\TokenGenerator;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Tests\Feature\Api\BaseApiHandlerTestCase;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\TextApiResponse;

class UserDeleteApiHandlerTest extends BaseApiHandlerTestCase
{
    /** @var UserDeleteApiHandler */
    private $handler;

    public function setUp(): void
    {
        parent::setUp();
        $this->handler = $this->getHandler(UserDeleteApiHandler::class);
    }

    public function testNoUserDataToDeleteError()
    {
        $email = 'example@example.com';
        $params = [
            'raw' => Json::encode([
                'email' => $email,
            ])
        ];

        /** @var JsonApiResponse $response */
        $response = $this->handler->handle($params);
        $payload = $response->getPayload();

        $this->assertInstanceOf(JsonApiResponse::class, $response);
        $this->assertEquals(IResponse::S404_NotFound, $response->getCode());
        $this->assertEquals('error', $payload['status']);
        $this->assertStringStartsWith('No user data found for email', $payload['message']);
        $this->assertStringContainsString($email, $payload['message']);
    }

    public function testUserDataDeletedSuccess()
    {
        [
            'template' => $mailTemplate,
            'mail_type_variant' => $mailTypeVariant,
            'batch' => $batch,
        ] = $this->prepareMailData();

        $email = 'example@example.com';
        $this->seedUserData($email, $mailTemplate, $mailTypeVariant, $batch);

        // create two more users with data to check if only one user's data were removed
        $this->seedUserData('other-user@example.com', $mailTemplate, $mailTypeVariant, $batch);
        $this->seedUserData('otter-animal@example.com', $mailTemplate, $mailTypeVariant, $batch);

        // check seed; 1 count of user's data; 3 together with two more users
        $this->assertEquals(3, $this->autoLoginTokensRepository->totalCount());
        $this->assertEquals(1, $this->autoLoginTokensRepository->getTable()->where('email', $email)->count('*'));
        $this->assertEquals(3, $this->jobQueueRepository->totalCount());
        $this->assertEquals(1, $this->jobQueueRepository->getTable()->where('email', $email)->count('*'));
        $this->assertEquals(3, $this->mailLogsRepository->totalCount());
        $this->assertEquals(1, $this->mailLogsRepository->getTable()->where('email', $email)->count('*'));
        $this->assertEquals(3, $this->mailLogConversionsRepository->totalCount());
        $this->assertEquals(1, $this->mailLogConversionsRepository->getTable()->where('mail_log.email', $email)->count('*'));
        $this->assertEquals(3, $this->userSubscriptionsRepository->totalCount());
        $this->assertEquals(1, $this->userSubscriptionsRepository->getTable()->where('user_email', $email)->count('*'));
        $this->assertEquals(3, $this->userSubscriptionVariantsRepository->totalCount());
        $this->assertEquals(1, $this->userSubscriptionVariantsRepository->getTable()->where('mail_user_subscription.user_email', $email)->count('*'));

        $params = [
            'raw' => Json::encode([
                'email' => $email,
            ])
        ];

        /** @var TextApiResponse $response */
        $response = $this->handler->handle($params);

        $this->assertInstanceOf(TextApiResponse::class, $response);
        $this->assertEquals(IResponse::S204_NoContent, $response->getCode());

        // check user data after removal; check if correct user was removed (has zero data); other two users should be untouched
        $this->assertEquals(2, $this->autoLoginTokensRepository->totalCount());
        $this->assertEquals(0, $this->autoLoginTokensRepository->getTable()->where('email', $email)->count('*'));
        $this->assertEquals(2, $this->jobQueueRepository->totalCount());
        $this->assertEquals(0, $this->jobQueueRepository->getTable()->where('email', $email)->count('*'));
        $this->assertEquals(2, $this->mailLogsRepository->totalCount());
        $this->assertEquals(0, $this->mailLogsRepository->getTable()->where('email', $email)->count('*'));
        $this->assertEquals(2, $this->mailLogConversionsRepository->totalCount());
        $this->assertEquals(0, $this->mailLogConversionsRepository->getTable()->where('mail_log.email', $email)->count('*'));
        $this->assertEquals(2, $this->userSubscriptionsRepository->totalCount());
        $this->assertEquals(0, $this->userSubscriptionsRepository->getTable()->where('user_email', $email)->count('*'));
        $this->assertEquals(2, $this->userSubscriptionVariantsRepository->totalCount());
        $this->assertEquals(0, $this->userSubscriptionVariantsRepository->getTable()->where('mail_user_subscription.user_email', $email)->count('*'));
    }

    /** MAIL DATA helper */

    private function prepareMailData()
    {
        $mailType = $this->createMailTypeWithCategory(
            "category1",
            "code1",
            "name1"
        );
        $mailLayout = $this->createMailLayout();
        $mailTemplate = $this->createTemplate($mailLayout, $mailType, 'mail_template_code_1');
        $mailTypeVariant = $this->createMailTypeVariant($mailTemplate->mail_type);
        $batch = $this->createJobAndBatch($mailTemplate, $mailTypeVariant);

        return [
            'template' => $mailTemplate,
            'mail_type_variant' => $mailTypeVariant,
            'batch' => $batch,
        ];
    }

    /** USER DATA helpers */

    private function seedUserData(string $email, ActiveRow $mailTemplate, ActiveRow $mailTypeVariant, ActiveRow $batch)
    {
        $mailLog = $this->createMailLog($email, $mailTemplate);
        $this->createMailLogConversion($mailLog);
        $this->createAutologinToken($email);
        $this->createJobQueue($email, $mailTemplate, $batch);
        $this->subscribeUserToVariant($email, $mailTypeVariant);
    }

    private function createMailLog(string $email, ActiveRow $mailTemplate): ActiveRow
    {
        return $this->mailLogsRepository->add(
            $email,
            'subject',
            $mailTemplate->id
        );
    }

    private function createMailLogConversion(ActiveRow $mailLog): void
    {
        $this->mailLogConversionsRepository->upsert($mailLog, new DateTime());
    }

    private function createAutologinToken(string $email): ActiveRow
    {
        return $this->autoLoginTokensRepository->insert($this->autoLoginTokensRepository->getInsertData(
            TokenGenerator::generate(),
            $email,
            new DateTime(),
            new DateTime(),
        ));
    }

    private function createJobQueue(string $email, ActiveRow $mailTemplate, ActiveRow $batch): ActiveRow
    {
        return $this->jobQueueRepository->insert([
            'mail_batch_id' => $batch->id,
            'mail_template_id' => $mailTemplate->id,
            'status' => JobQueueRepository::STATUS_NEW,
            'email' => $email,
        ]);
    }

    private function subscribeUserToVariant(string $email, ActiveRow $variant)
    {
        $this->userSubscriptionsRepository->subscribeUser($variant->mail_type, 1, $email, $variant->id);
    }
}
