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
        'host' => [
            'required' => true,
            'label' => 'SMTP host',
        ],
        'port' => [
            'required' => true,
            'label' => 'SMTP Port',
        ],
        'username' => [
            'required' => false,
            'label' => 'SMTP Username',
        ],
        'password' => [
            'required' => false,
            'label' => 'SMTP Password',
        ],
        'secure' => [
            'required' => false,
            'label' => 'SMTP Secure',
        ],
    ];

    public function __construct(
        Config $config,
        ConfigsRepository $configsRepository
    ) {
        parent::__construct($config, $configsRepository);

        // SMTP Mailer expects plain options
        $options = [];
        foreach ($this->options as $name => $option) {
            if (isset($option['value'])) {
                $options[$name] = $option['value'];
            }
        }

        $this->mailer = new \Nette\Mail\SmtpMailer($options);
    }

    public function send(Message $message)
    {
        $this->mailer->send($message);
    }
}
