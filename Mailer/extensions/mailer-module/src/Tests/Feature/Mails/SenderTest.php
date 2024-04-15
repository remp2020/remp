<?php
declare(strict_types=1);

namespace Tests\Feature\Mails;

use PHPUnit\Framework\Attributes\DataProvider;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Models\Mailer\EmailAllowList;
use Remp\MailerModule\Models\Sender;
use Remp\MailerModule\Models\Sender\MailerBatchException;
use Remp\MailerModule\Models\Sender\MailerFactory;
use Remp\MailerModule\Repositories\ConfigsRepository;
use Tests\Feature\BaseFeatureTestCase;

class SenderTest extends BaseFeatureTestCase
{
    protected Sender $applicationMailer;

    protected EmailAllowList $emailAllowList;

    protected ConfigsRepository $configsRepository;

    protected MailerFactory $mailerFactory;

    protected TestMailer $testMailer;

    private Config $config;

    protected array $usersList = [];

    protected $mailType;

    protected $mailTypeVariant;

    protected $mailLayout;

    protected $mailTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->applicationMailer = $this->inject(Sender::class);
        $this->emailAllowList = $this->inject(EmailAllowList::class);
        $this->configsRepository = $this->inject(ConfigsRepository::class);
        $this->mailerFactory = $this->inject(MailerFactory::class);
        $this->config = $this->inject(Config::class);

        $this->setDefaultMailer(TestMailer::ALIAS);

        $this->testMailer = $this->mailerFactory->getMailer(TestMailer::ALIAS);
        $this->testMailer->supportsBatch = true;
        $this->testMailer->clearSent();

        $this->emailAllowList->reset();

        $this->mailType = $this->createMailTypeWithCategory(
            'test_category',
            'test_mail_type',
            'Test Mail Type',
            true
        );

        $this->mailTypeVariant = $this->createMailTypeVariant($this->mailType);

        $this->mailLayout = $this->createMailLayout();
        $this->mailTemplate = $this->createTemplate($this->mailLayout, $this->mailType);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->truncate($this->configsRepository);
    }

    public static function dataProvider()
    {
        return [
            'SendEmailSuccess' => [
                'allowList' => [],
                'subscribedEmails' => [
                    '111@example.com',
                ],
                'recipientEmails' => [
                    '111@example.com',
                ],
                'expectedDeliveredEmails' => [
                    '111@example.com',
                ],
            ],
            'SendEmailFailBecauseUserIsNotSubscribed' => [
                'allowList' => [],
                'subscribedEmails' => [],
                'recipientEmails' => [
                    '111@example.com',
                ],
                'expectedDeliveredEmails' => [],
            ],
            'SendEmailSuccessWithUserInAllowList' => [
                'allowList' => [
                    '111@example.com',
                ],
                'subscribedEmails' => [
                    '111@example.com',
                ],
                'recipientEmails' => [
                    '111@example.com',
                ],
                'expectedDeliveredEmails' => [
                    '111@example.com',
                ],
            ],
            'SendEmailFailBecauseUserIsNotAllowList' => [
                'allowList' => [
                    '111@example.com',
                ],
                'subscribedEmails' => [
                    '222@example.com',
                ],
                'recipientEmails' => [
                    '222@example.com',
                ],
                'expectedDeliveredEmails' => [],
            ],
            'SendBatchToSubscribedUsersSuccess' => [
                'allowList' => [],
                'subscribedEmails' => [
                    '111@example.com',
                    '222@example.com',
                ],
                'recipientEmails' => [
                    '111@example.com',
                    '222@example.com',
                    '333@example.com',
                ],
                'expectedDeliveredEmails' => [
                    '111@example.com',
                    '222@example.com',
                ],
            ],
            'SendBatchToSubscribedAndAllowListedUsers' => [
                'allowList' => [
                    '111@example.com',
                    '333@example.com',
                ],
                'subscribedEmails' => [
                    '111@example.com',
                    '222@example.com',
                    '333@example.com',
                    '444@example.com',
                ],
                'recipientEmails' => [
                    '111@example.com',
                    '222@example.com',
                    '333@example.com',
                    '444@example.com',
                ],
                'expectedDeliveredEmails' => [
                    '111@example.com',
                    '333@example.com',
                ],
            ],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testSender(
        array $allowList = [],
        array $subscribedEmails = [],
        array $recipientEmails = [],
        array $expectedDeliveredEmails = []
    ) {
        foreach ($allowList as $allowEmail) {
            $this->emailAllowList->allow($allowEmail);
        }

        $userId = 1;
        foreach ($subscribedEmails as $subscribedEmail) {
            $user = $this->createUser($userId, $subscribedEmail);
            $this->createMailUserSubscription($this->mailType, $user['id'], $user['email'], $this->mailTypeVariant->id);
            $userId++;
        }

        $email = $this->applicationMailer
            ->reset()
            ->setTemplate($this->mailTemplate);

        foreach ($recipientEmails as $recipientEmail) {
            $email->addRecipient($recipientEmail);
        }

        if (count($recipientEmails) > 1) {
            $email->sendBatch();
        } else {
            $email->send();
        }

        $this->assertEqualsCanonicalizing($this->testMailer->getSentToEmails(), $expectedDeliveredEmails);
    }

    public function testSendEmailWithMailerSpecifiedInTemplateInsteadOfDefaultOne()
    {
        $this->setDefaultMailer('not_existing_mailer');
        $this->listsRepository->update($this->mailType, ['mailer_alias' => $this->testMailer->getMailerAlias()]);
        $this->emailAllowList->allow('111@example.com');

        $user = $this->createUser(1, '111@example.com');
        $this->createMailUserSubscription($this->mailType, $user['id'], $user['email'], $this->mailTypeVariant->id);

        $email = $this->applicationMailer
            ->reset()
            ->setTemplate($this->mailTemplate);

        $email->addRecipient($user['email']);
        $email->send();

        $this->assertEqualsCanonicalizing(['111@example.com'], $this->testMailer->getSentToEmails());
    }

    public function testDoNotSendEmailBecauseOfEmailWithSameContextAlreadySent()
    {
        $user1 = $this->createUser(1, '111@example.com');
        $this->createMailUserSubscription($this->mailType, $user1['id'], $user1['email'], $this->mailTypeVariant->id);

        $email = $this->applicationMailer
            ->reset()
            ->setTemplate($this->mailTemplate)
            ->setContext('testing.context');

        $email->addRecipient($user1['email']);
        $email->send();

        $this->assertEqualsCanonicalizing(['111@example.com'], $this->testMailer->getSentToEmails());

        $user2 = $this->createUser(2, '222@example.com');

        $email = $this->applicationMailer
            ->reset()
            ->setTemplate($this->mailTemplate)
            ->setContext('testing.context');

        $email->addRecipient($user2['email']);
        $email->send();

        $this->assertEqualsCanonicalizing(['111@example.com'], $this->testMailer->getSentToEmails());
    }

    public function testSendBatchFailBecauseMailerDoesntSupportBatchSending()
    {
        $this->testMailer->supportsBatch = false;

        $user = $this->createUser(1, '111@example.com');
        $this->createMailUserSubscription($this->mailType, $user['id'], $user['email'], $this->mailTypeVariant->id);

        $email = $this->applicationMailer
            ->reset()
            ->setTemplate($this->mailTemplate);

        $email->addRecipient($user['email']);
        $this->expectException(MailerBatchException::class);
        $email->sendBatch();
    }

    private function createUser(int $id, string $email)
    {
        $this->usersList[$id] = [
            'id' => $id,
            'email' => $email
        ];
        return $this->usersList[$id];
    }

    private function setDefaultMailer(string $name)
    {
        $defaultMailer = $this->configsRepository->findBy('name', 'default_mailer');
        if ($defaultMailer) {
            $this->configsRepository->update($defaultMailer, [
                'value' => $name
            ]);
        } else {
            $this->configsRepository->add('default_mailer', 'Default mailer', $name, '', '');
        }

        /**
         * Normally it isn't needed to force refresh as the possibly new source code/behavior is not reloaded yet and
         * the config isn't needed to be reloaded immediately (as it's needed in the test).
         */
        $this->config->refresh(force: true);
    }
}
