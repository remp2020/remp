<?php

namespace Remp\MailerModule\Repositories;

class MailTemplateLinksRepository extends Repository
{
    protected $tableName = 'mail_template_links';

    public function add(string $mailTemplateId, string $url, string $hash)
    {
        $sql = <<<SQL
INSERT IGNORE INTO `mail_template_links`(mail_template_id, url, hash, created_at)
VALUES (?, ?, ?, ?)
SQL;
        $this->database->query($sql, $mailTemplateId, $url, $hash, new \DateTime());
    }

    public function upsert(int $mailTemplateId, string $url, string $hash)
    {
        $exists = $this->findLinkByHash($hash);

        if (!isset($exists)) {
            $this->add($mailTemplateId, $url, $hash);
        }
    }

    public function findLinkByHash(string $hash): ?\Nette\Database\Table\ActiveRow
    {
        return $this->getTable()->where([
            'hash' => $hash
        ])->fetch();
    }

    public function getLinksForTemplate(\Nette\Database\Table\ActiveRow $template): array
    {
        $result = [];
        $links = $template->related('mail_template_links')->order('mail_template_links.id');
        foreach ($links as $link) {
            $result[$link->hash] = [
                'url' => $link->url,
                'clickCount' => $link->click_count
            ];
        }

        return $result;
    }

    public function incrementClickCount(\Nette\Database\Table\ActiveRow $mailTemplateLink)
    {
        $this->update($mailTemplateLink, [
            'click_count+=' => 1
        ]);
    }
}
