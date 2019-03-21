<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;

class MailTemplatesAggregatedDataRepository extends Repository
{
    protected $tableName = 'mail_templates_aggregated_data';

    public function byDateAndMailTemplateId(\DateTime $date, $id)
    {
        return $this->getTable()
            ->where('mail_template_id', $id)
            ->where('date', $date->format('Y-m-d'))
            ->fetch();
    }

    public function all()
    {
        return $this->getTable();
    }
}
