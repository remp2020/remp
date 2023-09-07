<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Mailer;

use Nette\Mail\Message;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Repositories\ConfigsRepository;
use Nette\Mail\SmtpMailer as NetteSmtpMailer;

class SmtpMailer extends Mailer
{
    public const ALIAS = 'remp_smtp';

    private NetteSmtpMailer $mailer;

    protected array $options = [
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
        ConfigsRepository $configsRepository,
        ?string $code = null,
    ) {
        parent::__construct($config, $configsRepository, $code);

        // SMTP Mailer expects plain options
        $options = [];
        foreach ($this->options as $name => $option) {
            if (isset($option['value'])) {
                $options[$name] = $option['value'];
            }
        }

        $this->mailer = new NetteSmtpMailer($options);
    }

    public function send(Message $message): void
    {
        // Removing this header prevents leaking template parameters that may contain sensitive information.
        // This header is historically used in MailgunMailer for passing template parameters to Mailgun.
        // We rather not pass this to SMTP mailer.
        $message->clearHeader('X-Mailer-Template-Params');

        $this->mailer->send($message);
    }
}
