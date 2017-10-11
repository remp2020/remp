<?php

namespace Remp\MailerModule;

use Nette\Database\IRow;
use Nette\Mail\Message;
use Nette\Utils\Json;
use Remp\MailerModule\Auth\AutoLogin;
use Remp\MailerModule\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\Sender\MailerFactory;

class Sender
{
    /** @var array */
    private $recipient;

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

    public function setRecipient($email, $name = null)
    {
        $this->recipient = [
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
        if ($checkEmailSubscribed && !$this->userSubscriptionsRepository->isEmailSubscribed($this->recipient['email'], $this->template->mail_type->id)) {
            return false;
        }

        if ($this->template->autologin) {
            $token = $this->autoLogin->createToken($this->recipient['email']);
            $this->params['autologin'] = "?token={$token->token}";
        }

        $message = new Message();
        $message->addTo($this->recipient['email'], $this->recipient['name']);
        $message->setFrom($this->template->from);
        $message->setSubject($this->template->subject);

        $generator = new ContentGenerator($this->template, $this->template->layout);
        if ($this->template->mail_body_text) {
            $message->setBody($generator->getTextBody($this->params));
        }

        if ($this->template->mail_body_html) {
            $message->setHtmlBody($generator->getHtmlBody($this->params));
        }

        $attachmentSize = null;
        foreach ($this->attachments as $name => $content) {
            $message->addAttachment($name, $content);
            $attachmentSize += strlen($content);
        }

        $senderId = md5($this->recipient['email'] . microtime(true));

        $message->setHeader('X-Mailer-Variables', Json::encode([
            'template' => $this->template->code,
            'job_id' => $this->jobId,
            'batch_id' => $this->batchId,
            'mail_sender_id' => $senderId,
        ]));

        $this->logsRepository->add(
            $this->recipient['email'],
            $this->template->subject,
            $this->template->id,
            $this->jobId,
            $this->batchId,
            $senderId,
            $attachmentSize
        );

        $this->mailerFactory->getMailer()->send($message);
        $this->reset();

        return true;
    }

    public function getMailerConfig($alias = null)
    {
        return $this->mailerFactory->getMailer($alias)->getConfig();
    }

    private function reset()
    {
        $this->recipient = null;
        $this->template = null;
        $this->jobId = null;
        $this->params = [];
        $this->attachments = [];
    }
}
