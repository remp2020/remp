<?php

namespace Remp\Mailer\Models\Generators;

class NapunkNewsfilterGenerator extends NewsfilterGenerator
{
    public function process(array $values): array
    {
        $this->articleLocker->setLockText('Ezt a cikket csak a Napunk előfizetői olvashatják végig.');
        $this->articleLocker->setupLockLink('Csatlakozz hozzánk', 'https://predplatne.dennikn.sk/napunk-start');

        return parent::process($values);
    }
}
