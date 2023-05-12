<?php

namespace Remp\MailerModule\Repositories;

use DateTime;

class MailTemplateLinkClicksRepository extends Repository
{
    protected $tableName = 'mail_template_link_clicks';

    public function add(\Nette\Database\Table\ActiveRow $mailTemplateLink, DateTime $clicked_at)
    {
        $result = $this->insert([
            'mail_template_link_id' => $mailTemplateLink->id,
            'clicked_at' => $clicked_at,
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }
}
