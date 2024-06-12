<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;
use Nette\Utils\Random;

class TemplatesRepository extends Repository
{
    use SoftDeleteTrait;

    protected $tableName = 'mail_templates';

    protected $dataTableSearchable = ['name', 'code', 'description', 'subject'];
    protected $dataTableSearchableFullText = ['mail_body_html'];

    public function all(): Selection
    {
        return $this->getTable()
            ->where('mail_templates.deleted_at', null)
            ->order('mail_templates.created_at DESC, mail_templates.id DESC');
    }

    public function pairs(int $listId): array
    {
        return $this->all()->select('id, name')->where(['mail_type_id' => $listId])->fetchPairs('id', 'name');
    }

    public function filteredPairs(int $listId, array $filterTemplateIds, ?int $limit = null): array
    {
        $query = $this->all()
            ->select('id, name')
            ->where([
                'mail_type_id' => $listId,
                'id NOT IN' => $filterTemplateIds
            ]);

        if (isset($limit)) {
            $query->limit($limit);
        }

        return $query->fetchPairs('id', 'name');
    }

    public function findByList(int $listId, ?int $limit = null): Selection
    {
        $query = $this->all()
            ->where([
                'mail_type_id' => $listId
            ]);

        if (isset($limit)) {
            $query->limit($limit);
        }

        return $query;
    }

    public function findByListCategory(int $listCategoryId, ?int $limit = null): Selection
    {
        $query = $this->all()
            ->where([
                'mail_type.mail_type_category_id' => $listCategoryId
            ])
            ->group('mail_templates.id');

        if (isset($limit)) {
            $query->limit($limit);
        }

        return $query;
    }

    public function triples(): array
    {
        $result = [];
        foreach ($this->all()->select('id, name, mail_type_id') as $template) {
            $result[$template->mail_type_id][] = [
                'value' => $template->id,
                'label' => $template->name,
            ];
        }
        return $result;
    }

    public function add(
        string $name,
        string $code,
        string $description,
        string $from,
        string $subject,
        string $templateText,
        string $templateHtml,
        int $layoutId,
        int $typeId,
        ?bool $clickTracking = null,
        ?string $extrasJson = null,
        ?string $paramsJson = null,
        bool $attachmentsEnabled = true
    ): ActiveRow {
        if ($this->exists($code)) {
            throw new TemplatesCodeNotUniqueException("Template code [$code] is already used.");
        }

        $result = $this->insert([
            'name' => $name,
            'code' => $code,
            'description' => $description,
            'from' => $from,
            'autologin' => true,
            'subject' => $subject,
            'click_tracking' => $clickTracking,
            'mail_body_text' => $templateText,
            'mail_body_html' => $templateHtml,
            'mail_layout_id' => $layoutId,
            'mail_type_id' => $typeId,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'extras' => $extrasJson,
            'params' => $paramsJson,
            'attachments_enabled' => $attachmentsEnabled,
            'public_code' => $this->generatePublicCode(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        // if code changed, check if it's unique
        if (isset($data['code']) && $row['code'] != $data['code'] && $this->exists($data['code'])) {
            throw new TemplatesCodeNotUniqueException("Template code [" . $data['code'] . "] is already used.");
        }
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    public function duplicate(ActiveRow $template)
    {
        return $this->insert([
            'name' => $template->name . ' (copy)',
            'code' => $this->getUniqueTemplateCode($template->code),
            'description' => $template->description,
            'from' => $template->from,
            'subject' => $template->subject,
            'mail_body_text' => $template->mail_body_text,
            'mail_body_html' => $template->mail_body_html,
            'mail_layout_id' => $template->mail_layout_id,
            'mail_type_id' => $template->mail_type_id,
            'copy_from' => $template->id,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'extras' => $template->extras,
            'params' => $template->params,
            'attachments_enabled' => $template->attachments_enabled,
            'public_code' => $this->generatePublicCode(),
        ]);
    }

    public function exists(string $code): bool
    {
        return $this->getTable()->where('code', $code)->count('*') > 0;
    }

    public function getByCode($code)
    {
        return $this->getTable()->where('code', $code)->fetch();
    }

    public function getByPublicCode($publicCode)
    {
        return $this->getTable()->where('public_code', $publicCode)->fetch();
    }

    public function getUniqueTemplateCode($codeBase)
    {
        $code = $codeBase;
        while ($this->exists($code)) {
            $code = $codeBase . '_' . Random::generate(4);
        }
        return $code;
    }

    public function tableFilter(string $query, string $order, string $orderDirection, ?array $mailTypeIds = null, ?array $mailLayoutIds = null, ?int $limit = null, ?int $offset = null): Selection
    {
        $selection = $this->getTable()
            ->where('deleted_at', null)
            ->order($order . ' ' . strtoupper($orderDirection));

        if (!empty($query)) {
            $where = [];
            foreach ($this->dataTableSearchable as $col) {
                $where[$col . ' LIKE ?'] = '%' . $query . '%';
            }

            foreach ($this->dataTableSearchableFullText as $col) {
                $where['MATCH('.$col . ') AGAINST(? IN BOOLEAN MODE)'] = '+' . $query . '*';
            }
            $selection->whereOr($where);
        }

        if ($mailTypeIds !== null) {
            $selection->where([
                'mail_type_id' => $mailTypeIds
            ]);
        }

        if ($mailLayoutIds !== null) {
            $selection->where([
                'mail_layout_id' => $mailLayoutIds
            ]);
        }

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }

    public function search(string $term, int $limit)
    {
        $searchable = ['code', 'name', 'subject', 'description'];
        foreach ($searchable as $column) {
            $whereFast[$column . ' LIKE ?'] = $term . '%';
            $whereWild[$column . ' LIKE ?'] = '%' . $term . '%';
        }

        $resultsFast = $this->all()
            ->whereOr($whereFast)
            ->limit($limit)
            ->fetchAll();
        if (count($resultsFast) === $limit) {
            return $resultsFast;
        }

        $resultsWild = $this->all()
            ->whereOr($whereWild)
            ->limit($limit - count($resultsFast))
            ->fetchAll();

        return array_merge($resultsFast, $resultsWild);
    }

    public function generatePublicCode()
    {
        return Random::generate(8);
    }

    public function getByMailTypeIds(array $mailTypeIds)
    {
        return $this->all()->where('mail_type_id', $mailTypeIds);
    }

    public function getByMailTypeCategoryCode(string $mailTypeCategoryCode): Selection
    {
        return $this->all()->where('mail_type.mail_type_category.code = ?', $mailTypeCategoryCode);
    }
}
