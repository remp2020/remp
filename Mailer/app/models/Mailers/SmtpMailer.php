<?php

namespace Remp\MailerModule\Mailer;

use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Remp\MailerModule\Config\Config;
use Remp\MailerModule\Repository\ConfigsRepository;

class SmtpMailer extends Mailer implements IMailer
{
    private $mailer;

    protected $alias = 'remp-smtp';

    protected $options = [
        'remp_mailermodule_mailer_smtpmailer_host' => null,
        'remp_mailermodule_mailer_smtpmailer_port' => null,
        'remp_mailermodule_mailer_smtpmailer_username' => null,
        'remp_mailermodule_mailer_smtpmailer_password' => null,
        'remp_mailermodule_mailer_smtpmailer_secure' => null,
    ];

    protected $requiredOptions = [
        'remp_mailermodule_mailer_smtpmailer_host',
        'remp_mailermodule_mailer_smtpmailer_port'
    ];

    public function __construct(
        Config $config,
        ConfigsRepository $configsRepository
    ) {
    
        parent::__construct($config, $configsRepository);
        $this->mailer = new \Nette\Mail\SmtpMailer($this->options);
    }

    public function send(Message $message)
    {
        $this->mailer->send($message);
    }
}
