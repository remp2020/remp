<?php

namespace Remp\MailerModule\Mailer;

use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Remp\MailerModule\Config\Config;

class SmtpMailer implements IMailer
{
    private $mailer;

    private $config;

    public function __construct(Config $config)
    {
        $this->config = [
            'host' => $config->get('smtp_host'),
            'port' => $config->get('smtp_port'),
            'username' => $config->get('smtp_username'),
            'password' => $config->get('smtp_password'),
            'secure' => $config->get('smtp_secure'),
        ];

        $this->mailer = new \Nette\Mail\SmtpMailer($this->config);
    }

    public function send(Message $mail)
    {
        $this->mailer->send($mail);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}
