<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Job;

use Remp\MailerModule\Repositories\JobQueueRepository;

class MailJobQueueBatchInserter
{
    private const BATCH_COUNT = 200;
    private array $data = [];

    public function __construct(private readonly JobQueueRepository $jobQueueRepository)
    {
    }

    public function add(array $jobData): void
    {
        $this->data[] = $jobData;

        if (count($this->data) >= self::BATCH_COUNT) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if ($this->data) {
            $this->jobQueueRepository->multiInsert($this->data);
            $this->data = [];
        }
    }
}
