<?php

namespace Remp\MailerModule\Repository;

interface IConversionsRepository
{
    public function getBatchTemplateConversions($batchId, $mailTemplateCode): array;

    public function getNonBatchTemplateConversions($mailTemplateCode): array;
}
