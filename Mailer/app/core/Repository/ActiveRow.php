<?php
declare(strict_types=1);

namespace Remp\MailerModule;

use Nette\Database\Table\ActiveRow as NetteActiveRow;

class ActiveRow extends NetteActiveRow
{
    public function delete()
    {
        throw new \Exception('Direct delete is not allowed, use repository\'s update');
    }

    public function update($data)
    {
        throw new \Exception('Direct update is not allowed, use repository\'s update');
    }
}
