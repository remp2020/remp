<?php

namespace Remp\MailerModule\Repository;

interface IConversionsRepository
{
    public function getBatchTemplatesConversions(array $batchIds, array $mailTemplateCodes): array;

    public function getNonBatchTemplateConversions(array $mailTemplateCodes): array;
}
