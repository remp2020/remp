<?php
declare(strict_types=1);

use Nette\Utils\Strings;
use Phinx\Migration\AbstractMigration;

final class AddCodeToMailTypeCategories extends AbstractMigration
{
    const TABLE_NAME = 'mail_type_categories';

    public function up(): void
    {
        $this->table(self::TABLE_NAME)
            ->addColumn('code', 'string', ['null' => true, 'after' => 'title'])
            ->addIndex(['code'], ['unique' => true])
            ->update();

        foreach ($this->fetchAll("select * from " . self::TABLE_NAME) as $row) {
            $code = Strings::webalize($row['title']);
            $this->execute("UPDATE " . self::TABLE_NAME . " SET code='$code' WHERE id={$row['id']}");
        }

        $this->table(self::TABLE_NAME)
            ->changeColumn('code', 'string', ['null' => false])
            ->update();
    }

    public function down(): void
    {
        $this->table(self::TABLE_NAME)
            ->removeColumn('code')
            ->update();
    }
}
