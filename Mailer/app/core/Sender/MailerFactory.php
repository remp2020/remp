<?php

namespace Remp\MailerModule\Sender;

use Nette\Mail\IMailer;
use Remp\MailerModule\Config\Config;
use Remp\MailerModule\Mailer\Mailer;

class MailerFactory
{
    /** @var  Config */
    private $config;

    /** @var array */
    private $availableMailers;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param IMailer $mailer
     */
    public function addMailer(IMailer $mailer)
    {
        $this->availableMailers[get_class($mailer)] = $mailer;
    }

    /**
     * @return IMailer|Mailer
     */
    public function getMailer()
    {
        return $this->availableMailers[$this->config->get('default_mailer')];
    }

    /**
     * @return IMailer[]
     */
    public function getAvailableMailers()
    {
        return $this->availableMailers;
    }
}
