<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;
use Remp\MailerModule\Selection;

class ListsRepository extends Repository
{
    protected $tableName = 'mail_types';

    protected $dataTableSearchable = ['code', 'title', 'description'];

    public function all()
    {
        return $this->getTable()->order('sorting ASC');
    }

    public function add($code, $name, $description, $order, $isConsentRequired, $isLocked, $isPublic)
    {
        $this->updateOrder(null, $order);

        $result = $this->insert([
            'code' => $code,
            'title' => $name,
            'description' => $description,
            'sorting' => $order,
            'auto_subscribe' => !(bool)$isConsentRequired,
            'locked' => (bool)$isLocked,
            'is_public' => (bool)$isPublic,
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function update(IRow &$row, $data)
    {
        $this->updateOrder($row->sorting, $data['sorting']);

        $params['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }

    public function updateOrder($oldOrder, $newOrder)
    {
        if ($oldOrder == $newOrder) {
            return;
        }

        if ($oldOrder !== null) {
            $this->getTable()->where('sorting > ?', $oldOrder)->update(['sorting-=' => 1]);
        }

        $this->getTable()->where('sorting > ?', $newOrder)->update(['sorting+=' => 1]);
    }

    /**
     * @return Selection
     */
    public function tableFilter()
    {
        $selection = $this->getTable()
            ->order('mail_type_category.sorting')
            ->group('mail_types.id');

        return $selection;
    }
}
