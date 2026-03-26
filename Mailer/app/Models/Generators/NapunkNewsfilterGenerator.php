<?php

namespace Remp\Mailer\Models\Generators;

class NapunkNewsfilterGenerator extends NewsfilterGenerator
{
    public function process(array $values): array
    {
        $this->n3ArticleLocker->setLockText('Ezt a cikket csak a Napunk előfizetői olvashatják végig.');
        $this->n3ArticleLocker->setLockLink('Csatlakozz hozzánk', 'https://predplatne.dennikn.sk/napunk-start');

        return parent::process($values);
    }
}
