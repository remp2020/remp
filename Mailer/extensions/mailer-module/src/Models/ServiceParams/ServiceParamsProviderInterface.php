<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\ServiceParams;

use Nette\Database\Table\ActiveRow;

interface ServiceParamsProviderInterface
{
    public function provide(ActiveRow $template, string $email, ?int $batchId = null, ?string $autoLogin = null): array;
}
