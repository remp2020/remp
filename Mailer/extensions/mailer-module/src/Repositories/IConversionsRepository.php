<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

interface IConversionsRepository
{
    public function getBatchTemplatesConversionsSince(\DateTime $since): array;

    public function getNonBatchTemplatesConversionsSince(\DateTime $since): array;
}
