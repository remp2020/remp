<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\Selection;
use Remp\MailerModule\Repository;
use Nette\Utils\DateTime;

class SourceTemplatesRepository extends Repository
{
    protected $tableName = 'mail_source_template';

    protected $dataTableSearchable = ['title'];

    public function all()
    {
        return $this->getTable()->select('*')->order('sorting DESC');
    }

    public function add($title, $generator, $html, $text, $sorting = 100)
    {
        return $this->insert([
            'title' => $title,
            'generator' => $generator,
            'content_html' => $html,
            'content_text' => $text,
            'sorting' => $sorting,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ]);
    }

    public function exists($title)
    {
        return $this->getTable()->where('title', $title)->count('*');
    }

    public function findLast()
    {
        return $this->getTable()->order('sorting DESC')->limit(1);
    }

    public function renderContent($sourceTemplate, $data)
    {
        $loader = new \Twig_Loader_Array([
            'textTemplate' => $sourceTemplate->content_text,
            'htmlTemplate' => $sourceTemplate->content_html,
        ]);

        $twig = new \Twig_Environment($loader);

        return [
            'text' => $twig->render('textTemplate', $data),
            'html' => $twig->render('htmlTemplate', $data),
        ];
    }

    /**
     * @param $query
     * @param $order
     * @param $orderDirection
     * @param null $limit
     * @param null $offset
     *
     * @return Selection
     */
    public function tableFilter($query, $order, $orderDirection, $limit = null, $offset = null)
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

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }
}
