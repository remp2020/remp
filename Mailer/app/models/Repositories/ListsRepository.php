<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
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

    public function add(
        $categoryId,
        $priority,
        $code,
        $name,
        $sorting,
        $isAutoSubscribe,
        $isLocked,
        $isPublic,
        $description,
        $previewUrl = null,
        $imageUrl = null,
        $publicListing = true
    ) {
        $result = $this->insert([
            'mail_type_category_id' => $categoryId,
            'priority' => $priority,
            'code' => $code,
            'title' => $name,
            'description' => $description,
            'sorting' => $sorting,
            'auto_subscribe' => (bool)$isAutoSubscribe,
            'locked' => (bool)$isLocked,
            'is_public' => (bool)$isPublic,
            'public_listing' => (bool)$publicListing,
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

    public function update(IRow &$row, $data)
    {
        unset($data['id']);
        $data['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }

    public function updateSorting($newCategoryId, $newSorting, $oldCategoryId = null, $oldSorting = null)
    {
        if ($newSorting === $oldSorting) {
            return;
        }

        if ($oldSorting !== null) {
            $this->getTable()
                ->where(
                    'sorting > ? AND mail_type_category_id = ?',
                    $oldSorting,
                    $oldCategoryId
                )->update(['sorting-=' => 1]);
        }

        $this->getTable()->where(
            'sorting >= ? AND mail_type_category_id = ?',
            $newSorting,
            $newCategoryId
        )->update(['sorting+=' => 1]);
    }

    public function findByCode($code)
    {
        return $this->getTable()->where(['code' => $code]);
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
