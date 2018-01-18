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
        $this->availableMailers[$mailer->getAlias()] = $mailer;
    }

    /**
     * @param null|string $alias
     * @return IMailer|Mailer
     * @throws MailerNotExistsException
     */
    public function getMailer($alias = null)
    {
        if ($alias === null) {
            $alias = $this->config->get('default_mailer');
        }

        if (!isset($this->availableMailers[$alias])) {
            throw new MailerNotExistsException("Mailer {$alias} not exists");
        }

        return $this->availableMailers[$alias];
    }

    /**
     * @return IMailer[]
     */
    public function getAvailableMailers()
    {
        return $this->availableMailers;
    }
}
