<?php

namespace Remp\MailerModule\Sender;

use Nette\Mail\IMailer;
use Remp\MailerModule\Config\Config;

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
     * @return IMailer
     */
    public function getMailer()
    {
        return $this->availableMailers[$this->config->get('default.mailer')];
    }

    /**
     * @return IMailer[]
     */
    public function getAvailableMailers()
    {
        return $this->availableMailers;
    }
}
