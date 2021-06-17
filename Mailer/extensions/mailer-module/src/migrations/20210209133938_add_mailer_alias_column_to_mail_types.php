<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMailerAliasColumnToMailTypes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('mail_types')
            ->addColumn(
                'mailer_alias',
                'string',
                [
                    'null' => true,
                    'after' => 'default_variant_id'
                ]
            )
            ->update();
    }
}
