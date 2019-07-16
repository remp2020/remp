<?php

namespace Remp\MailerModule\Repository;

use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Remp\MailerModule\Repository;
use Tomaj\Hermes\MessageInterface;

class HermesTasksRepository extends Repository
{
    const STATE_DONE  = 'done';
    const STATE_ERROR = 'error';

    protected $tableName = 'hermes_tasks';

    public function add(MessageInterface $message, $state)
    {
        $createdAt = DateTime::from(strtotime($message->getCreated()));

        return $this->insert([
            'message_id' => $message->getId(),
            'type' => $message->getType(),
            'payload' => Json::encode($message->getPayload()),
            'retry' => $message->getRetries(),
            'state' => $state,
            'created_at' => $createdAt,
            'execute_at' => $message->getExecuteAt() ? $message->getExecuteAt() : null,
            'processed_at' => new DateTime(),
        ]);
    }

    public function getStateCounts()
    {
        return $this->getTable()->group('state, type')->select('state, type, count(*) AS count')->order('count DESC');
    }
}
