<?php

namespace Remp\MailerModule;

use Nette\Database\IRow;
use Nette\Mail\Message;
use Nette\Utils\Json;
use Remp\MailerModule\Auth\AutoLogin;
use Remp\MailerModule\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\Sender\MailerBatchException;
use Remp\MailerModule\Sender\MailerFactory;

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

    public function addRecipient($email, $name = null)
    {
        $this->recipients[] = [
            'email' => $email,
            'name' => $name,
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

    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    public function send($checkEmailSubscribed = true)
    {
        if (count($this->recipients) > 1) {
            throw new MailerBatchException("attempted to send batch via send() method: please use single recipient");
        }
        $recipient = reset($this->recipients);

        if ($checkEmailSubscribed && !$this->userSubscriptionsRepository->isEmailSubscribed($recipient['email'], $this->template->mail_type->id)) {
            return false;
        }

        if ($this->template->autologin) {
            $token = $this->autoLogin->createToken($recipient['email']);
            $this->params['autologin'] = "?token={$token->token}";
        }

        $mailer = $this->mailerFactory->getMailer();

        $message = new Message();
        $message->addTo($recipient['email'], $recipient['name']);
        $message->setFrom($this->template->from);
        $message->setSubject($this->template->subject);

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

        $this->logsRepository->add(
            $recipient['email'],
            $this->template->subject,
            $this->template->id,
            $this->jobId,
            $this->batchId,
            $senderId,
            $attachmentSize
        );

        $mailer->send($message);
        $this->reset();

        return true;
    }

    public function sendBatch()
    {
        $mailer = $this->mailerFactory->getMailer();
        if (!$mailer->supportsBatch()) {
            throw new MailerBatchException(
                sprintf('attempted to send batch via %s mailer: not supported', $mailer->getAlias())
            );
        }

        $templateParams = [];

        $message = new Message();
        $message->setFrom($this->template->from);
        $message->setSubject($this->template->subject);

        foreach ($this->recipients as $recipient) {
            if (!$this->userSubscriptionsRepository->isEmailSubscribed($recipient['email'], $this->template->mail_type->id)) {
                continue;
            }

            $p = $this->params;
            $p['mail_sender_id'] = md5($recipient['email'] . microtime(true));

            if ($this->template->autologin) {
                $token = $this->autoLogin->createToken($recipient['email']);
                $p['autologin'] = "?token={$token->token}";
            }

            list($transformedParams, $p) = $mailer->transformTemplateParams($p);
            $templateParams[$recipient['email']] = $p;

            $message->addTo($recipient['email'], $recipient['name']);
        }

        $generator = new ContentGenerator($this->template, $this->template->layout, $this->batchId);

        if ($this->template->mail_body_text) {
            $message->setBody($generator->getTextBody($transformedParams));
        }

        if ($this->template->mail_body_html) {
            $message->setHtmlBody($generator->getHtmlBody($transformedParams));
        }

        $attachmentSize = $this->setMessageAttachments($message);

        $this->setMessageHeaders($message, '%recipient.mail_sender_id%', $templateParams);

        $insertLogsData = [];
        foreach ($templateParams as $email => $params) {
            $insertLogsData[] = [
                'email' => $email,
                'subject' => $this->template->subject,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
                'mail_template_id' => $this->template->id,
                'mail_job_id' => $this->jobId,
                'mail_job_batch_id' => $this->batchId,
                'mail_sender_id' => $params['mail_sender_id'],
                'attachment_size' => $attachmentSize,
            ];
        }
        $logsTableName = $this->logsRepository->getTable()->getName();
        $this->logsRepository->getDatabase()->query("INSERT INTO $logsTableName", $insertLogsData);

        $mailer->send($message);
        $this->reset();

        return true;
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
        ]));
        $message->setHeader('X-Mailer-Tag', $this->template->code);
        $message->setHeader('X-Mailer-Template-Params', Json::encode($templateParams));
    }

    public function getMailerConfig($alias = null)
    {
        return $this->mailerFactory->getMailer($alias)->getConfig();
    }

    private function reset()
    {
        $this->recipients = [];
        $this->template = null;
        $this->jobId = null;
        $this->params = [];
        $this->attachments = [];
    }

    public function supportsBatch()
    {
        return $this->mailerFactory->getMailer()->supportsBatch();
    }
}
