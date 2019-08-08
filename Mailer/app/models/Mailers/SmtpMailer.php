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
            'description' => 'IP address or hostname of SMTP server (e.g. 127.0.0.1)',
        ],
        'port' => [
            'required' => true,
            'label' => 'SMTP Port',
            'description' => 'Port on which your SMTP server is exposed (e.g. 1025)',
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
            'description' => 'Secure protocol used to connect (e.g. ssl)',
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
