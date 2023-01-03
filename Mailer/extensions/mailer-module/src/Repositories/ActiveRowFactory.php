<?php

namespace Remp\MailerModule\Repositories;

use Nette\Database\Conventions\StaticConventions;
use Nette\Database\Explorer;

class ActiveRowFactory
{
    public const TABLE_NAME_DATAROW = 'datarow';

    private Explorer $explorer;

    public function __construct(Explorer $explorer)
    {
        $this->explorer = $explorer;
    }

    public function create(array $data): ActiveRow
    {
        $staticConventions = new StaticConventions();

        $selection = new Selection(
            $this->explorer,
            $staticConventions,
            'datarow'
        );

        return new ActiveRow($data, $selection);
    }
}
