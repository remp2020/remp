<?php

namespace Remp\MailerModule;

use Nette\Database\IRow;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Utils\AssertionException;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Remp\MailerModule\Auth\AutoLogin;
use Remp\MailerModule\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Mailer\Mailer;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\Sender\MailerBatchException;
use Remp\MailerModule\Sender\MailerFactory;
use Remp\MailerModule\Sender\MailerNotExistsException;
use Twig_Environment;
use Twig_Loader_Array;

class Sender
{
    /** @var array */
    private $recipients = [];

    /** @var \Nette\Database\Table\ActiveRow */
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

    /** @var MailerFactory */
    private $mailerFactory;

    /** @var AutoLogin */
    private $autoLogin;

    /** @var UserSubscriptionsRepository */
    private $userSubscriptionsRepository;

    /** @var LogsRepository */
    private $logsRepository;

    public function __construct(
        MailerFactory $mailerFactory,
        AutoLogin $autoLogin,
        UserSubscriptionsRepository $userSubscriptionsRepository,
        LogsRepository $logsRepository
    ) {
        $this->mailerFactory = $mailerFactory;
        $this->autoLogin = $autoLogin;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->logsRepository = $logsRepository;
    }

    public function addRecipient(string $email, string $name = null, array $params = [])
    {
        $this->recipients[] = [
            'email' => $email,
            'name' => $name,
            'params' => $params
        ];

        return $this;
    }

    public function addAttachment($name, $content = null)
    {
        $this->attachments[$name] = $content;

        return $this;
    }

    public function setTemplate(IRow $template)
    {
        $this->template = $template;

        return $this;
    }

    public function setJobId($jobId)
    {
        $this->jobId = $jobId;

        return $this;
    }

    public function setBatchId($batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    public function send($checkEmailSubscribed = true): int
    {
        if (count($this->recipients) > 1) {
            throw new MailerBatchException(sprintf("attempted to send batch via send() method: please use single recipient: %s", Json::encode($this->recipients)));
        }
        $recipient = reset($this->recipients);

        if ($checkEmailSubscribed && !$this->userSubscriptionsRepository->isEmailSubscribed($recipient['email'], $this->template->mail_type->id)) {
            return 0;
        }

        $tokens = $this->autoLogin->createTokens([$recipient['email']]);
        $this->params['autologin'] = "?token={$tokens[$recipient['email']]}";
        $this->params = array_merge($this->params, $recipient['params'] ?? []);

        if (getenv('UNSUBSCRIBE_URL')) {
            $this->params['unsubscribe'] = str_replace(getenv('UNSUBSCRIBE_URL'), '%type%', $this->template->mail_type->code) . $this->params['autologin'];
        }
        if (getenv('SETTINGS_URL')) {
            $this->params['settings'] = getenv('SETTINGS_URL') . $this->params['autologin'];
        }

        $mailer = $this->getMailer();

        $message = new Message();
        $message->addTo($recipient['email'], $recipient['name']);
        $message->setFrom($this->template->from);
        $message->setSubject($this->generateSubject($this->template->subject, $this->params));

        $generator = new ContentGenerator($this->template, $this->template->layout, $this->batchId);
        if ($this->template->mail_body_text) {
            $message->setBody($generator->getTextBody($this->params));
        }
        if ($this->template->mail_body_html) {
            $message->setHtmlBody($generator->getHtmlBody($this->params));
        }

        $attachmentSize = $this->setMessageAttachments($message);

        $senderId = md5($recipient['email'] . microtime(true));
        $this->setMessageHeaders($message, $senderId, $this->params);

        if ($this->context) {
            $alreadySent = $this->logsRepository->alreadySentContext($this->context);
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
                sprintf('attempted to send batch via %s mailer: not supported', $mailer->getAlias())
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

            [$transformedParams, $p] = $mailer->transformTemplateParams($p);
            $templateParams[$recipient['email']] = $p;
        }

        if ($logger !== null) {
            $logger->info("Sender - template params transformed for {$this->batchId}", [
                'transformedParams' => $transformedParams
            ]);
        }

        $message->setSubject($this->generateSubject($this->template->subject, $transformedParams));

        $generator = new ContentGenerator($this->template, $this->template->layout, $this->batchId);

        foreach ($templateParams as $email => $params) {
            $templateParams[$email] = $generator->getEmailParams($params);
        }

        if ($logger !== null) {
            $logger->info("Sender - email params generated for {$this->batchId}");
        }

        if ($this->template->mail_body_text) {
            $message->setBody($generator->getTextBody($transformedParams));
        }

        if ($logger !== null) {
            $logger->info("Sender - text content generated for {$this->batchId}");
        }

        if ($this->template->mail_body_html) {
            $message->setHtmlBody($generator->getHtmlBody($transformedParams));
        }

        if ($logger !== null) {
            $logger->info("Sender - html content generated for {$this->batchId}");
        }

        $attachmentSize = $this->setMessageAttachments($message);

        $this->setMessageHeaders($message, '%recipient.mail_sender_id%', $templateParams);

        $insertLogsData = [];
        foreach ($templateParams as $email => $params) {
            $insertLogsData[] = $this->logsRepository->getInsertData(
                $email,
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

    private function setMessageHeaders(Message $message, $mailSenderId, $templateParams): void
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
    }

    /**
     * @param null|string $alias - If $alias is null, default mailer is returned.
     * @return IMailer|Mailer
     * @throws MailerNotExistsException
     */
    public function getMailer($alias = null)
    {
        return $this->mailerFactory->getMailer($alias);
    }

    public function reset()
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

    private function generateSubject($subjectTemplate, $params): string
    {
        $loader = new Twig_Loader_Array([
            'my_template' => $subjectTemplate,
        ]);
        $twig = new Twig_Environment($loader);
        return $twig->render('my_template', $params);
    }
}
