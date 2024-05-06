<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;

class ConfigsRepository extends Repository
{
    protected $tableName = 'configs';

    public function all()
    {
        return $this->getTable()->order('sorting ASC');
    }

    public function add(string $name, string $displayName, $value, ?string $description, string $type): ActiveRow
    {
        $result = $this->insert([
            'name' => $name,
            'display_name' => $displayName,
            'value' => $value,
            'description' => $description,
            'type' => $type,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function loadByName(string $name): ?ActiveRow
    {
        return $this->getTable()->where('name', $name)->fetch();
    }

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }
}
