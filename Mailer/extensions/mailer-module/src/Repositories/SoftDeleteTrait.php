<?php

namespace Remp\MailerModule\Repositories;

use Nette\Utils\Random;

trait SoftDeleteTrait
{
    public function softDelete(ActiveRow $row)
    {
        $this->update($row, [
            'code' => $row->code . '_DELETED_' . Random::generate(8),
            'updated_at' => new \DateTime(),
            'deleted_at' => new \DateTime(),
        ]);
    }
}
