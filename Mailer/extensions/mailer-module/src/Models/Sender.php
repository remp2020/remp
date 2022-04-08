<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models;

use Nette\Mail\Message;
use Nette\Utils\AssertionException;
use Remp\MailerModule\Models\Config\ConfigNotExistsException;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Repositories\ActiveRow;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Remp\MailerModule\Models\Auth\AutoLogin;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Mailer\Mailer;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Models\Sender\MailerBatchException;
use Remp\MailerModule\Models\Sender\MailerFactory;
use Remp\MailerModule\Models\Sender\MailerNotExistsException;

class Sender
{
    /** @var array */
    private $recipients = [];

    /** @var ActiveRow */
    private $template;

    /** @var int|null */
    private $jobId = null;

    /** @var int|null */
    private $batchId = null;

    /** @var  array */
    private $params = [];

    /** @var  array */
    private $attachments = [];

    /** @var string */
    private $context;

    private $mailerFactory;

    private $autoLogin;

    private $userSubscriptionsRepository;

    private $logsRepository;

    private $engineFactory;

    private $contentGenerator;

    private $generatorInputFactory;

    public function __construct(
        MailerFactory $mailerFactory,
        AutoLogin $autoLogin,
        UserSubscriptionsRepository $userSubscriptionsRepository,
        LogsRepository $logsRepository,
        EngineFactory $engineFactory,
        ContentGenerator $contentGenerator,
        GeneratorInputFactory $generatorInputFactory
    ) {
        $this->mailerFactory = $mailerFactory;
        $this->autoLogin = $autoLogin;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->logsRepository = $logsRepository;
        $this->engineFactory = $engineFactory;
        $this->contentGenerator = $contentGenerator;
        $this->generatorInputFactory = $generatorInputFactory;
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

    public function send(bool $checkEmailSubscribed = true): int
    {
        if (count($this->recipients) > 1) {
            throw new MailerBatchException(sprintf("attempted to send batch via send() method: please use single recipient: %s", Json::encode($this->recipients)));
        }
        $recipient = reset($this->recipients);

        if ($checkEmailSubscribed && !$this->userSubscriptionsRepository->isEmailSubscribed($recipient['email'], $this->template->mail_type->id)) {
            return 0;
        }

        $tokens = $this->autoLogin->createTokens([$recipient['email']]);
        $this->params['email'] = $recipient['email'];
        $this->params['autologin'] = "?token={$tokens[$recipient['email']]}";
        $this->params = array_merge($this->params, $recipient['params'] ?? []);
        $this->params = array_merge($this->params, $this->generateServiceParams($this->params['autologin']));

        $mailer = $this->getMailer();

        $message = new Message();
        $message->addTo($recipient['email'], $recipient['name']);
        $message->setFrom($this->template->from);
        $message->setSubject($this->generateSubject($this->template->subject, $this->params));

        $mailContent = $this->contentGenerator->render($this->generatorInputFactory->create(
            $this->template,
            $this->params,
            $this->batchId
        ));

        if ($this->template->mail_body_text) {
            $message->setBody($mailContent->text());
        }
        if ($this->template->mail_body_html) {
            $message->setHtmlBody($mailContent->html());
        }

        $attachmentSize = $this->setMessageAttachments($message);

        $senderId = md5($recipient['email'] . microtime(true));
        $this->setMessageHeaders($message, $senderId, $this->params);

        if ($this->context) {
            $alreadySent = $this->logsRepository->alreadySentContext($recipient['email'], $this->context);
            if ($alreadySent) {
                return 0;
            }
        }

        $this->logsRepository->add(
            $recipient['email'],
            $this->template->subject,
            $this->template->id,
            $this->jobId,
            $this->batchId,
            $senderId,
            $attachmentSize,
            $this->context
        );

        $mailer->send($message);
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
        $message->setFrom($this->template->from);

        $subscribedEmails = [];
        foreach ($this->recipients as $recipient) {
            $subscribedEmails[] = $recipient['email'];
        }
        if ($logger !== null) {
            $logger->info("Sender - sending batch {$this->batchId}", [
                'recipients_count' => count($subscribedEmails)
            ]);
        }
        $subscribedEmails = $this->userSubscriptionsRepository->filterSubscribedEmails($subscribedEmails, $this->template->mail_type_id);

        if ($logger !== null) {
            $logger->info("Sender - subscribers filtering before sending {$this->batchId}", [
                'recipients_count_after_filtering' => count($subscribedEmails)
            ]);
        }

        $autologinTokens = $this->autoLogin->createTokens($subscribedEmails);

        $transformedParams = [];
        foreach ($this->recipients as $recipient) {
            if (!isset($subscribedEmails[$recipient['email']]) || !$subscribedEmails[$recipient['email']]) {
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
            $p['mail_sender_id'] = md5($recipient['email'] . microtime(true));
            $p['autologin'] = "?token={$autologinTokens[$recipient['email']]}";
            $p['email'] = $recipient['email'];
            $p = array_merge($p, $this->generateServiceParams($p['autologin']));

            [$transformedParams, $p] = $mailer->transformTemplateParams($p);
            $templateParams[$recipient['email']] = $p;
        }

        if ($logger !== null) {
            $logger->info("Sender - template params transformed for {$this->batchId}", [
                'transformedParams' => $transformedParams
            ]);
        }

        $message->setSubject($this->generateSubject($this->template->subject, $transformedParams));

        $generatorInput = $this->generatorInputFactory->create(
            $this->template,
            $transformedParams,
            $this->batchId
        );

        foreach ($templateParams as $email => $params) {
            $templateParams[$email] = $this->contentGenerator->getEmailParams($generatorInput, $params);
        }

        if ($logger !== null) {
            $logger->info("Sender - email params generated for {$this->batchId}");
        }

        $mailContent = $this->contentGenerator->render($generatorInput);
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

        $this->setMessageHeaders($message, '%recipient.mail_sender_id%', $templateParams);

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
                $this->context
            );
        }
        $logsTableName = $this->logsRepository->getTable()->getName();
        $this->logsRepository->getDatabase()->query("INSERT INTO $logsTableName", $insertLogsData);

        if ($logger !== null) {
            $logger->info("Sender - mail logs stored for {$this->batchId}");
        }

        $mailer->send($message);
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

    private function setMessageHeaders(Message $message, $mailSenderId, ?array $templateParams): void
    {
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

        if (isset($templateParams['unsubscribe']) && !$this->template->mail_type->locked) {
            $message->setHeader('List-Unsubscribe', '<' . $templateParams['unsubscribe'] . '>');
        }
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
            $alias = $this->template->mail_type->mailer_alias;
        }

        return $this->mailerFactory->getMailer($alias);
    }

    public function reset(): self
    {
        $this->recipients = [];
        $this->template = null;
        $this->jobId = null;
        $this->batchId = null;
        $this->params = [];
        $this->attachments = [];
        $this->context = null;

        return $this;
    }

    public function supportsBatch()
    {
        return $this->getMailer()->supportsBatch();
    }

    private function generateSubject(string $subjectTemplate, array  $params): string
    {
        return $this->engineFactory->engine()->render($subjectTemplate, $params);
    }

    private function generateServiceParams(?string $autologin)
    {
        $params = [];
        if (isset($_ENV['UNSUBSCRIBE_URL'])) {
            $params['unsubscribe'] = str_replace('%type%', $this->template->mail_type->code, $_ENV['UNSUBSCRIBE_URL']) . $autologin;
        }
        if (isset($_ENV['SETTINGS_URL'])) {
            $params['settings'] = $_ENV['SETTINGS_URL'] . $autologin;
        }
        return $params;
    }
}
