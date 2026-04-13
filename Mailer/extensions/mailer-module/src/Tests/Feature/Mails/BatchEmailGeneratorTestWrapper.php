<?php
declare(strict_types=1);

namespace Tests\Feature\Mails;

use Remp\MailerModule\Models\Job\BatchEmailGenerator;
use Remp\MailerModule\Repositories\ActiveRow;

class BatchEmailGeneratorTestWrapper extends BatchEmailGenerator
{
    // To enable public access to protected functions in tests
    public function insertUsersIntoJobQueue(ActiveRow $batch, &$userMap): array
    {
        return parent::insertUsersIntoJobQueue($batch, $userMap);
    }

    public function filterQueue($batch): array
    {
        return parent::filterQueue($batch);
    }
}
