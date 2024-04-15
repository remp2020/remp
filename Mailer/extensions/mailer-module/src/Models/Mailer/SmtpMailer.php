<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Mailer;

use Nette\Mail\Message;
use Nette\Mail\SmtpMailer as NetteSmtpMailer;

class SmtpMailer extends Mailer
{
    public const ALIAS = 'remp_smtp';

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

    public function send(Message $mail): void
    {
        // Removing this header prevents leaking template parameters that may contain sensitive information.
        // This header is historically used in MailgunMailer for passing template parameters to Mailgun.
        // We rather not pass this to SMTP mailer.
        $mail->clearHeader('X-Mailer-Template-Params');

        $mailer = $this->createMailer();
        $mailer->send($mail);
    }

    private function createMailer(): NetteSmtpMailer
    {
        $this->buildConfig();

        return new NetteSmtpMailer(
            host: $this->options['host']['value'] ?? '',
            username: $this->options['username']['value'] ?? '',
            password: $this->options['password']['value'] ?? '',
            port: (int) $this->options['port']['value'] ?? null,
            encryption: $this->options['secure']['value'] ?? null,
        );
    }
}
