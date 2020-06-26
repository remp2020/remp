<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;
use Remp\MailerModule\Selection;

class TemplatesRepository extends Repository
{
    protected $tableName = 'mail_templates';

    protected $dataTableSearchable = ['name', 'code', 'description', 'subject', 'mail_body_text', 'mail_body_html'];

    public function all()
    {
        return $this->getTable()->order('created_at DESC');
    }

    public function pairs($listId)
    {
        return $this->all()->select('id, name')->where(['mail_type_id' => $listId])->fetchPairs('id', 'name');
    }

    public function triples()
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
        $name,
        $code,
        $description,
        $from,
        $subject,
        $templateText,
        $templateHtml,
        $layoutId,
        $typeId,
        $extras = null
    ) {

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
            'mail_body_text' => $templateText,
            'mail_body_html' => $templateHtml,
            'mail_layout_id' => $layoutId,
            'mail_type_id' => $typeId,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
            'extras' => $extras
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function update(IRow &$row, $data)
    {
        // if code changed, check if it's unique
        if (isset($data['code']) && $row['code'] != $data['code'] && $this->exists($data['code'])) {
            throw new TemplatesCodeNotUniqueException("Template code [" . $data['code'] . "] is already used.");
        }
        $params['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }

    public function duplicate(IRow $template)
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
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
            'extras' => $template->extras
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

    public function getUniqueTemplateCode($codeBase)
    {
        $index = 0;
        do {
            $code = $codeBase . '-' . $index;
            if ($index == 0) {
                $code = $codeBase;
            }
            $index++;
        } while ($this->exists($code));

        return $code;
    }

    /**
     * @param $query
     * @param $order
     * @param $orderDirection
     * @param null $listIds
     * @param null $limit
     * @param null $offset
     *
     * @return Selection
     */
    public function tableFilter($query, $order, $orderDirection, $listIds = null, $limit = null, $offset = null)
    {
        $selection = $this->getTable()
            ->order($order . ' ' . strtoupper($orderDirection));

        if (!empty($query)) {
            $where = [];
            foreach ($this->dataTableSearchable as $col) {
                $where[$col . ' LIKE ?'] = '%' . $query . '%';
            }

            $selection->whereOr($where);
        }

        if ($listIds !== null) {
            $selection->where([
                'mail_type_id' => $listIds
            ]);
        }

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }

    public function search(string $term, int $limit): \Nette\Database\Table\Selection
    {
        $searchable = ['code', 'name', 'subject', 'description'];
        foreach ($searchable as $column) {
            $where[$column . ' LIKE ?'] = '%' . $term . '%';
        }

        return $this->all()
            ->whereOr($where ?? [])
            ->limit($limit);
    }
}
