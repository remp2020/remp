<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Database\Table\ActiveRow as NetteActiveRow;

class ActiveRow extends NetteActiveRow
{
    public function delete(): int
    {
        throw new \Exception('Direct delete is not allowed, use repository\'s update');
    }

    public function update(iterable $data): bool
    {
        throw new \Exception('Direct update is not allowed, use repository\'s update');
    }
}
