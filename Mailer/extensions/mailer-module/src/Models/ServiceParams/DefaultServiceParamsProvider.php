<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\ServiceParams;

class DefaultServiceParamsProvider implements ServiceParamsProviderInterface
{
    public function provide($template, string $email, ?int $batchId = null, ?string $autoLogin = null): array
    {
        $params = [];
        if (isset($_ENV['UNSUBSCRIBE_URL'])) {
            $params['unsubscribe'] = str_replace('%type%', $template->mail_type->code, $_ENV['UNSUBSCRIBE_URL']) . $autoLogin;
        }
        if (isset($_ENV['SETTINGS_URL'])) {
            $params['settings'] = $_ENV['SETTINGS_URL'] . $autoLogin;
        }
        return $params;
    }
}
