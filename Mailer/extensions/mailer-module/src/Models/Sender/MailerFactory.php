<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Sender;

use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Models\Mailer\Mailer;

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

    public function addMailer(Mailer $mailer): void
    {
        $this->availableMailers[$mailer->getMailerAlias()] = $mailer;
    }

    /**
     * @param null|string $alias - If $alias is null, default mailer is returned.
     * @return Mailer
     * @throws MailerNotExistsException|\Remp\MailerModule\Models\Config\ConfigNotExistsException
     */
    public function getMailer(?string $alias = null): Mailer
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
     * @return Mailer[]
     */
    public function getAvailableMailers(): array
    {
        return $this->availableMailers;
    }

    public function getMailerByAliasAndCode($alias, $code): Mailer
    {
        return $this->getMailer(Mailer::buildAlias($alias, $code));
    }
}
