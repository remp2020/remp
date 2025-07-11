<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Nette\Database\Table\ActiveRow;
use Nette\Mail\Message;
use Nette\Utils\AssertionException;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Remp\MailerModule\Models\Auth\AutoLogin;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Models\Config\ConfigNotExistsException;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Models\Mailer\EmailAllowList;
use Remp\MailerModule\Models\Mailer\Mailer;
use Remp\MailerModule\Models\Sender\MailerBatchException;
use Remp\MailerModule\Models\Sender\MailerFactory;
use Remp\MailerModule\Models\Sender\MailerNotExistsException;
use Remp\MailerModule\Models\Sender\MailerRuntimeException;
use Remp\MailerModule\Models\ServiceParams\ServiceParamsProviderInterface;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tracy\Debugger;
use Tracy\ILogger;

class Sender
{
    private array $recipients = [];
    private ActiveRow $template;
    private ?int $jobId = null;
    private ?int $batchId = null;
    private array $params = [];
    private array $attachments = [];
    private ?string $context = null;
    private ?string $locale = null;

    public function __construct(
        private MailerFactory $mailerFactory,
        private AutoLogin $autoLogin,
        private UserSubscriptionsRepository $userSubscriptionsRepository,
        private LogsRepository $logsRepository,
        private ContentGenerator $contentGenerator,
        private GeneratorInputFactory $generatorInputFactory,
        private ServiceParamsProviderInterface $serviceParamsProvider,
        private EmailAllowList $emailAllowList,
        private Config $config
    ) {
    }

    public function addRecipient(string $email, string $name = null, array $params = []): self
    {
        $this->recipients[] = [
            'email' => $email,
            'name' => $name,
            'params' => $params
        ];

        return $this;
    }

    public function addAttachment(string $name, $content = null): self
    {
        $this->attachments[$name] = $content;

        return $this;
    }

    public function setTemplate(ActiveRow $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function setJobId(int $jobId): self
    {
        $this->jobId = $jobId;

        return $this;
    }

    public function setBatchId(int $batchId): self
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function setLocale(string $locale = null): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function send(bool $checkEmailSubscribed = true): int
    {
        if (count($this->recipients) > 1) {
            throw new MailerBatchException(sprintf("attempted to send batch via send() method: please use single recipient: %s", Json::encode($this->recipients)));
        }
        $recipient = reset($this->recipients);

        $subscription = $this->userSubscriptionsRepository->getEmailSubscription($this->template->mail_type, $recipient['email']);
        if ($checkEmailSubscribed && (!$subscription || !$subscription->subscribed)) {
            return 0;
        }

        if (!$this->emailAllowList->isAllowed($recipient['email'])) {
            return 0;
        }

        $tokens = $this->autoLogin->createTokens([$recipient['email']]);
        $this->params['email'] = $recipient['email'];
        $this->params['autologin'] = "?token={$tokens[$recipient['email']]}";
        $this->params = array_merge($this->params, $recipient['params'] ?? []);

        $serviceParams = $this->serviceParamsProvider->provide(
            $this->template,
            $recipient['email'],
            $this->batchId,
            $this->params['autologin'],
        );

        $this->params = array_merge($this->params, $serviceParams);

        $mailer = $this->getMailer();

        $message = new Message();
        $message->addTo($recipient['email'], $recipient['name']);

        $generatorInput = $this->generatorInputFactory->create(
            $this->template,
            $this->params,
            $this->batchId,
            $this->locale
        );

        $contentGeneratorContext = ['status' => 'sending'];
        $mailContent = $this->contentGenerator->render($generatorInput, $contentGeneratorContext);

        $message->setSubject($mailContent->subject());
        $message->setFrom($mailContent->from());

        if (!empty($mailContent->text())) {
            $message->setBody($mailContent->text());
        }
        if (!empty($mailContent->html())) {
            $message->setHtmlBody($mailContent->html());
        }

        $attachmentSize = $this->setMessageAttachments($message);

        $senderId = $this->getSenderId($recipient['email']);
        $this->setMessageHeaders($message, $senderId, [$recipient['email'] => $this->params]);

        if (isset($this->context)) {
            $alreadySent = $this->logsRepository->alreadySentContext($recipient['email'], $this->context);
            if ($alreadySent) {
                return 0;
            }
        }

        $this->logsRepository->add(
            $recipient['email'],
            $message->getSubject(),
            $this->template->id,
            $this->jobId,
            $this->batchId,
            $senderId,
            $attachmentSize,
            $this->context,
            $subscription->user_id ?? null
        );

        try {
            $mailer->send($message);
        } catch (MailerRuntimeException $exception) {
            $this->logsRepository->getTable()->where([
                'mail_sender_id' => $senderId,
                'email' => $recipient['email'],
                'context' => $this->context,
            ])->delete();

            throw new MailerRuntimeException($exception->getMessage());
        }

        $this->reset();

        return 1;
    }

    public function sendBatch(LoggerInterface $logger = null): int
    {
        $mailer = $this->getMailer();
        if (!$mailer->supportsBatch()) {
            throw new MailerBatchException(
                sprintf('attempted to send batch via %s mailer: not supported', $mailer->getMailerAlias())
            );
        }

        $templateParams = [];

        $message = new Message();

        $subscribedEmails = [];
        foreach ($this->recipients as $recipient) {
            $subscribedEmails[] = $recipient['email'];
        }
        if ($logger !== null) {
            $logger->info("Sender - sending batch {$this->batchId}", [
                'recipients_count' => count($subscribedEmails)
            ]);
        }
        $subscribedEmails = $this->userSubscriptionsRepository->filterSubscribedEmailsAndIds($subscribedEmails, $this->template->mail_type_id);

        if ($logger !== null) {
            $logger->info("Sender - subscribers filtering before sending {$this->batchId}", [
                'recipients_count_after_filtering' => count($subscribedEmails)
            ]);
        }

        $autologinTokens = $this->autoLogin->createTokens(array_keys($subscribedEmails));

        $transformedParams = [];
        foreach ($this->recipients as $recipient) {
            if (!isset($subscribedEmails[$recipient['email']]) || !$subscribedEmails[$recipient['email']]) {
                continue;
            }

            if (!$this->emailAllowList->isAllowed($recipient['email'])) {
                continue;
            }

            try {
                $message->addTo($recipient['email'], $recipient['name']);
            } catch (AssertionException $e) {
                // we do nothing; it's invalid email and we want to skip it ASAP
                if ($logger !== null) {
                    $logger->warning("Sender - invalid email for {$this->batchId}", [
                        'error' => $e->getMessage(),
                        'email' => $recipient['email'],
                    ]);
                }
            }

            $p = array_merge($this->params, $recipient['params'] ?? []);
            $p['mail_sender_id'] = $this->getSenderId($recipient['email']);
            $p['autologin'] = "?token={$autologinTokens[$recipient['email']]}";
            $p['email'] = $recipient['email'];

            $serviceParams = $this->serviceParamsProvider->provide(
                $this->template,
                $recipient['email'],
                $this->batchId,
                $p['autologin'],
            );

            $p = array_merge($p, $serviceParams);

            [$transformedParams, $p] = $mailer->transformTemplateParams($p);
            $templateParams[$recipient['email']] = $p;
        }

        if ($logger !== null) {
            $logger->info("Sender - template params transformed for {$this->batchId}", [
                'transformedParams' => $transformedParams
            ]);
        }

        $generatorInput = $this->generatorInputFactory->create(
            $this->template,
            $transformedParams,
            $this->batchId
        );
        $contentGeneratorContext = ['status' => 'sending', 'sendingMode' => 'batch'];

        foreach ($templateParams as $email => $params) {
            $templateParams[$email] = $this->contentGenerator->getEmailParams(
                $generatorInput,
                $params,
                $contentGeneratorContext
            );
        }

        if ($logger !== null) {
            $logger->info("Sender - email params generated for {$this->batchId}");
        }

        $mailContent = $this->contentGenerator->render($generatorInput, $contentGeneratorContext);

        $message->setFrom($mailContent->from());
        $message->setSubject($mailContent->subject());

        if ($this->template->mail_body_text) {
            $message->setBody($mailContent->text());
        }

        if ($logger !== null) {
            $logger->info("Sender - text content generated for {$this->batchId}");
        }

        if ($this->template->mail_body_html) {
            $message->setHtmlBody($mailContent->html());
        }

        if ($logger !== null) {
            $logger->info("Sender - html content generated for {$this->batchId}");
        }

        $attachmentSize = $this->setMessageAttachments($message);

        $this->setMessageHeaders($message, '%recipient.mail_sender_id%', $templateParams, true);

        $insertLogsData = [];
        foreach ($templateParams as $email => $params) {
            $insertLogsData[] = $this->logsRepository->getInsertData(
                (string) $email,
                $this->template->subject,
                $this->template->id,
                $this->jobId,
                $this->batchId,
                $params['mail_sender_id'],
                $attachmentSize,
                $this->context,
                (int) $subscribedEmails[$email]
            );
        }
        $logsTableName = $this->logsRepository->getTable()->getName();
        $this->logsRepository->getDatabase()->query("INSERT INTO $logsTableName", $insertLogsData);

        if ($logger !== null) {
            $logger->info("Sender - mail logs stored for {$this->batchId}");
        }

        try {
            $mailer->send($message);
        } catch (MailerRuntimeException $exception) {
            $deleteLogsData = array_map(function ($item) {
                return [$item['email'], $item['mail_sender_id']];
            }, $insertLogsData);

            $this->logsRepository->getDatabase()->query("DELETE FROM $logsTableName WHERE (email, mail_sender_id) IN ?", $deleteLogsData);

            throw new MailerRuntimeException($exception->getMessage());
        }

        $this->reset();

        return count($subscribedEmails);
    }

    private function setMessageAttachments(Message $message): ?int
    {
        $attachmentSize = null;
        foreach ($this->attachments as $name => $content) {
            $message->addAttachment($name, $content);
            $attachmentSize += strlen($content);
        }
        return $attachmentSize;
    }

    private function setMessageHeaders(Message $message, $mailSenderId, ?array $templateParams, bool $isBatch = false): void
    {
        $enableOneClickUnsubscribing = $this->config->get('one_click_unsubscribe');
        if (!$this->template->mail_type->locked) {
            if ($isBatch) {
                $message->setHeader('List-Unsubscribe', '%recipient.list_unsubscribe%');
                if ($enableOneClickUnsubscribing) {
                    $message->setHeader('List-Unsubscribe-Post', '%recipient.list_unsubscribe_post%');
                }
                foreach ($templateParams as $email => $variables) {
                    if (isset($variables['unsubscribe'])) {
                        $templateParams[$email]['list_unsubscribe'] = "<{$variables['unsubscribe']}>";
                        if ($enableOneClickUnsubscribing) {
                            $templateParams[$email]['list_unsubscribe_post'] = 'List-Unsubscribe=One-Click';
                        }
                    }
                }
            } else {
                foreach ($templateParams as $email => $variables) {
                    if (isset($variables['unsubscribe'])) {
                        $message->setHeader('List-Unsubscribe', "<{$variables['unsubscribe']}>");
                        if ($enableOneClickUnsubscribing) {
                            $message->setHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                        }
                    }
                }
            }
        }

        if ($this->locale !== null) {
            $message->setHeader('Content-Language', str_replace('_', '-', $this->locale));
        }

        $message->setHeader('X-Mailer-Variables', Json::encode([
            'template' => $this->template->code,
            'job_id' => $this->jobId,
            'batch_id' => $this->batchId,
            'mail_sender_id' => $mailSenderId,
            'context' => $this->context,
        ]));
        $message->setHeader('X-Mailer-Tag', $this->template->code);
        $message->setHeader('X-Mailer-Template-Params', Json::encode($templateParams));
        // intentional string type-case, integer would be ignored
        $message->setHeader('X-Mailer-Click-Tracking', (string) $this->template->click_tracking);
    }

    /**
     * @param null|string $alias - If $alias is null, default mailer is returned.
     * @return Mailer
     * @throws MailerNotExistsException
     * @throws ConfigNotExistsException
     */
    public function getMailer($alias = null): Mailer
    {
        if ($alias === null && $this->template !== null) {
            return $this->getMailerByTemplate($this->template);
        }

        return $this->mailerFactory->getMailer($alias);
    }

    public function getMailerByTemplate($template)
    {
        $alias = $template->mail_type->mailer_alias;
        return $this->mailerFactory->getMailer($alias);
    }

    public function reset(): self
    {
        $this->recipients = [];
        unset($this->template);
        $this->jobId = null;
        $this->batchId = null;
        $this->params = [];
        $this->attachments = [];
        $this->context = null;
        $this->locale = null;

        return $this;
    }

    public function supportsBatch()
    {
        return $this->getMailer()->supportsBatch();
    }

    private function getSenderId(string $email): string
    {
        $time = microtime(true);
        return hash("crc32c", $email . $time) . (int) $time;
    }
}
