<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Tomaj\Hermes\MessageInterface;

class HermesTasksRepository extends Repository
{
    const STATE_DONE  = 'done';
    const STATE_ERROR = 'error';

    protected $tableName = 'hermes_tasks';


    public function add(MessageInterface $message, string $state): ActiveRow
    {
        $createdAt = DateTime::createFromFormat('U.u', sprintf('%.4f', $message->getCreated()));
        $executeAt = $message->getExecuteAt() ?
            DateTime::createFromFormat('U.u', sprintf('%.4f', $message->getExecuteAt())) :
            null;

        return $this->insert([
            'message_id' => $message->getId(),
            'type' => $message->getType(),
            'payload' => Json::encode($message->getPayload()),
            'retry' => $message->getRetries(),
            'state' => $state,
            'created_at' => $createdAt,
            'execute_at' => $executeAt,
            'processed_at' => new DateTime(),
        ]);
    }

    public function getStateCounts(): array
    {
        return $this->getTable()->group('state, type')->select('state, type, count(*) AS count')->order('count DESC');
    }
}
