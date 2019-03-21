<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;

class MailTemplatesAggregatedDataRepository extends Repository
{
    protected $tableName = 'mail_templates_aggregated_data';

    /**
     * @param \DateTime $date
     * @param $id
     * @return false|\Nette\Database\Table\ActiveRow
     */
    public function byDateAndMailTemplateId(\DateTime $date, $id)
    {
        return $this->getTable()
            ->where('mail_template_id', $id)
            ->where('date', $date->format('Y-m-d'))
            ->fetch();
    }

    /**
     * @param $id
     * @return \Remp\MailerModule\Selection
     */
    public function byMailTemplateId($id)
    {
        return $this->getTable()
            ->where('mail_template_id', $id);
    }

    /**
     * @return \Remp\MailerModule\Selection
     */
    public function all()
    {
        return $this->getTable();
    }
}
