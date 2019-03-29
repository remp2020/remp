<?php

namespace Remp\MailerModule\Repository;

use DateTime;
use Remp\MailerModule\Repository;

class MailTemplateStatsRepository extends Repository
{
    protected $tableName = 'mail_template_stats';

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
     * @param $id
     * @return \Remp\MailerModule\Selection
     */
    public function byMailTypeId($id)
    {
        return $this->getTable()
            ->where('mail_template.mail_type_id', $id)
            ->group('mail_template.mail_type_id');
    }

    /**
     * @return \Remp\MailerModule\Selection
     */
    public function all()
    {
        return $this->getTable();
    }

    /**
     * @param $mailTypeId
     * @param DateTime $from
     * @param DateTime $to
     * @return \Remp\MailerModule\Selection
     */
    public function getMailTypeGraphData($mailTypeId, DateTime $from, DateTime $to)
    {
        return $this->getTable()
            ->select('
                SUM(COALESCE(mail_template_stats.sent, 0)) AS sent_mails,
                SUM(COALESCE(mail_template_stats.opened, 0)) AS opened_mails,
                SUM(COALESCE(mail_template_stats.clicked, 0)) AS clicked_mails,
                date AS label_date')
            ->where('mail_template.mail_type_id = ?', $mailTypeId)
            ->where('date >= DATE(?)', $from)
            ->where('date <= DATE(?)', $to)
            ->group('
                label_date
            ')
            ->order('label_date DESC');
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return \Remp\MailerModule\Selection
     */
    public function getAllMailTemplatesGraphData(DateTime $from, DateTime $to)
    {
        return $this->getTable()
            ->select('
                SUM(COALESCE(mail_template_stats.sent, 0)) AS sent_mails,
                date
            ')
            ->where('date > DATE(?)', $from)
            ->where('date <= DATE(?)', $to)
            ->group('date');
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return \Remp\MailerModule\Selection
     */
    public function getTemplatesGraphDataGroupedByMailType(DateTime $from, DateTime $to)
    {
        return $this->getTable()
            ->select('
                SUM(COALESCE(mail_template_stats.sent, 0)) AS sent_mails,
                mail_template.mail_type_id,
                date
            ')
            ->where('mail_template.mail_type_id IS NOT NULL')
            ->where('mail_template_stats.date >= DATE(?)', $from)
            ->where('mail_template_stats.date <= DATE(?)', $to)
            ->group('
                date,
                mail_template.mail_type_id
            ')
            ->order('mail_template.mail_type_id')
            ->order('mail_template_stats.date DESC');
    }
}
