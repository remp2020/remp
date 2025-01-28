<?php

namespace Remp\MailerModule\Commands;

use Nette\Utils\DateTime;
use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Models\Segment\Crm;
use Remp\MailerModule\Models\Tracker\EventOptions;
use Remp\MailerModule\Models\Tracker\ITracker;
use Remp\MailerModule\Models\Tracker\User;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tomaj\Hermes\Emitter;

class UnsubscribeInactiveUsersCommand extends Command
{
    use DecoratedCommandTrait, RedisClientTrait;

    public const COMMAND_NAME = 'mail:unsubscribe-inactive-users';
    private const CRM_SEGMENT_NAME = 'unsubscribe_inactive_users_from_newsletters_list';
    public const APPLE_BOT_EMAILS = 'apple_bot_emails';

    private array $omitMailTypeCodes = ['system', 'system_optional'];

    public function __construct(
        private Aggregator $segmentAggregator,
        private UserSubscriptionsRepository $userSubscriptionsRepository,
        private TemplatesRepository $templatesRepository,
        private LogsRepository $logsRepository,
        private Emitter $hermesEmitter,
        RedisClientFactory $redisClientFactory,
        private ITracker|null $tracker = null
    ) {
        parent::__construct();

        $this->redisClientFactory = $redisClientFactory;
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Unsubscribe inactive users from newsletters')
            ->addOption('segment-provider', 'sp', InputOption::VALUE_REQUIRED, 'Segment provider code.', Crm::PROVIDER_ALIAS)
            ->addOption('segment', 's', InputOption::VALUE_REQUIRED, 'Crm segment with list of users to unsubscribe.', self::CRM_SEGMENT_NAME)
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Days limit for check opened emails.', 45)
            ->addOption('disable-apple-bot-check', 'dabc', InputOption::VALUE_NONE, 'Do not make distinction between Apple bot and real user, while checking opened/clicked.')
            ->addOption('email', 'e', InputOption::VALUE_REQUIRED, 'Email template code of notification email, sent after unsubscribing.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run command without unsubscribing users and sending notifications.')
            ->addOption('omit-mail-type-code', 'o', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Omit mail type (code), from email delivered and opened check and unsubscribing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $segmentProvider = $input->getOption('segment-provider');
        $segment = $input->getOption('segment');
        $dayLimit = $input->getOption('days');
        $dryRun = $input->getOption('dry-run');
        $disableAppleBotCheck = $input->getOption('disable-apple-bot-check');
        $notificationEmailCode = $input->getOption('email');
        $omitMailTypeCodes = array_merge($this->omitMailTypeCodes, $input->getOption('omit-mail-type-code'));

        if ($notificationEmailCode) {
            $notificationEmailTemplate = $this->templatesRepository->getByCode($input->getOption('email'));
            if (!$notificationEmailTemplate) {
                $this->error("Notification email template: '{$input->getOption('email')}' doesn't exist.");
                return Command::FAILURE;
            }
        }

        $userIds = $this->segmentAggregator->users(['provider' => $segmentProvider, 'code' => $segment]);

        foreach ($userIds as $userId) {
            $output->write("* Checking user <info>{$userId}</info>: ");
            $subscribed = $this->userSubscriptionsRepository->getTable()
                ->where('user_id', $userId)
                ->where('mail_type.code NOT IN', $omitMailTypeCodes)
                ->where('subscribed', 1)
                ->fetch();

            if ($subscribed) {
                $userEmail = $subscribed->user_email;
                $logs = $this->logsRepository->getTable()
                    ->where('user_id', $userId)
                    ->where('delivered_at > ', DateTime::from("-{$dayLimit} days"))
                    ->where('mail_template.mail_type.code NOT IN', $omitMailTypeCodes)
                    ->fetchAll();

                $logCount = count($logs);
                if (count($logs) >= 5) {
                    $isAppleBotOpenedEmail = $disableAppleBotCheck ? false : $this->redis()->sismember(self::APPLE_BOT_EMAILS, $userEmail);

                    foreach ($logs as $log) {
                        if ($isAppleBotOpenedEmail && $log->clicked_at) {
                            $output->writeln("Skipping, user is active.");
                            continue 2;
                        }

                        if (!$isAppleBotOpenedEmail && ($log->opened_at || $log->clicked_at)) {
                            $output->writeln("Skipping, user is active.");
                            continue 2;
                        }
                    }

                    if (!$dryRun) {
                        $output->write("<comment>Unsubscribing...</comment> ");
                        $this->userSubscriptionsRepository->unsubscribeUserFromAll($userId, $userEmail, $omitMailTypeCodes);

                        $eventOptions = new EventOptions();
                        $eventOptions->setUser(new User(['id' => $userId]));
                        $this->tracker?->trackEvent(
                            new DateTime(),
                            'mail-type',
                            'auto-unsubscribe',
                            $eventOptions
                        );

                        if (isset($notificationEmailTemplate)) {
                            $today = new DateTime();
                            $this->hermesEmitter->emit(new HermesMessage('send-email', [
                                'mail_template_code' => $notificationEmailTemplate->code,
                                'email' => $userEmail,
                                'context' => "nl_goodbye_all_email.{$userId}.{$notificationEmailTemplate->mail_type->id}.{$today->format('Ymd')}",
                            ]));
                        }
                    } else {
                        $output->write("<comment>Unsubscribing (dry run)...</comment> ");
                    }

                    $output->writeln("OK!");
                } else {
                    $output->writeln("Skipping, not enough delivered emails within the {$dayLimit} days period ({$logCount}).");
                }
            } else {
                $output->writeln("Skipping, not subscribed to anything relevant.");
            }
        }

        return Command::SUCCESS;
    }
}
