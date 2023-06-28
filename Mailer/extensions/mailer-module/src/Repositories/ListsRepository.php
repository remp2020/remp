<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;

class ListsRepository extends Repository
{
    use SoftDeleteTrait;

    protected $tableName = 'mail_types';

    protected $dataTableSearchable = ['code', 'title', 'description'];

    public function all(): Selection
    {
        return $this->getTable()
            ->where('deleted_at', null)
            ->order('sorting ASC');
    }

    public function add(
        int $categoryId,
        int $priority,
        string $code,
        string $name,
        int $sorting,
        bool $isAutoSubscribe,
        bool $isLocked,
        string $description,
        ?string $previewUrl = null,
        ?string $pageUrl = null,
        ?string $imageUrl = null,
        bool $publicListing = true,
        ?string $mailFrom = null,
        int $subscribeEmailTemplateId = null,
        int $unSubscribeEmailTemplateId = null,
        bool $isMultiVariant = false,
        int $defaultVariantId = null,
    ): ActiveRow {
        $result = $this->insert([
            'mail_type_category_id' => $categoryId,
            'priority' => $priority,
            'code' => $code,
            'title' => $name,
            'mail_from' => $mailFrom,
            'description' => $description,
            'sorting' => $sorting,
            'auto_subscribe' => $isAutoSubscribe,
            'locked' => $isLocked,
            'public_listing' => $publicListing,
            'image_url' => $imageUrl,
            'preview_url' => $previewUrl,
            'page_url' => $pageUrl,
            'subscribe_mail_template_id' => $subscribeEmailTemplateId,
            'unsubscribe_mail_template_id' => $unSubscribeEmailTemplateId,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'is_multi_variant' => $isMultiVariant,
            'default_variant_id' => $defaultVariantId,
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        unset($data['id']);
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    public function updateSorting(int $newCategoryId, int $newSorting, ?int $oldCategoryId = null, ?int $oldSorting = null): void
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

    public function findByCode(string $code): Selection
    {
        return $this->getTable()->where(['code' => $code]);
    }

    public function findByCategory(int $categoryId): Selection
    {
        return $this->getTable()->where(['mail_type_category_id' => $categoryId]);
    }

    public function tableFilter(): Selection
    {
        return $this->getTable()
            ->where('deleted_at', null)
            ->order('mail_type_category.sorting, mail_types.sorting');
    }

    public function search(string $term, int $limit): array
    {
        foreach ($this->dataTableSearchable as $column) {
            $where[$column . ' LIKE ?'] = '%' . $term . '%';
        }

        $results = $this->all()
            ->select(implode(',', array_merge(['id'], $this->dataTableSearchable)))
            ->whereOr($where ?? [])
            ->limit($limit)
            ->fetchAssoc('id');

        return $results;
    }

    public function getUsedMailersAliases(): array
    {
        return $this->getTable()->select('DISTINCT mailer_alias')
            ->where('deleted_at', null)
            ->where('mailer_alias IS NOT NULL')
            ->fetchPairs(null, 'mailer_alias');
    }

    public function canBeDeleted(ActiveRow $list): bool
    {
        return !(bool) $list->related('mail_templates')->where('deleted_at', null)->count('*');
    }
}
