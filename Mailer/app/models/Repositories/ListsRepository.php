<?php

namespace Remp\MailerModule\Repository;

use Nette\Utils\DateTime;
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

    public function add($categoryId, $priority, $code, $name, $order, $isAutoSubscribe, $isLocked, $isPublic, $description = null, $previewUrl = null, $imageUrl = null)
    {
        $this->updateOrder($categoryId, $order);

        $result = $this->insert([
            'mail_type_category_id' => $categoryId,
            'priority' => $priority,
            'code' => $code,
            'title' => $name,
            'description' => $description,
            'sorting' => $order,
            'auto_subscribe' => (bool)$isAutoSubscribe,
            'locked' => (bool)$isLocked,
            'is_public' => (bool)$isPublic,
            'image_url' => $imageUrl,
            'preview_url' => $previewUrl,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime()
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function updateOrder($categoryId, $order)
    {
        $this->getTable()
            ->where(['mail_type_category_id' => $categoryId])
            ->where('sorting > ?', $order)
            ->update(['sorting+=' => 1]);
    }

    public function findByCategory($categoryId)
    {
        return $this->getTable()->where(['mail_type_category_id' => $categoryId]);
    }

    /**
     * @return Selection
     */
    public function tableFilter()
    {
        return $this->getTable()->order('mail_type_category.sorting, mail_types.sorting');
    }
}
