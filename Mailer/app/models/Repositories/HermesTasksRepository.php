<?php

namespace Remp\MailerModule\Repository;

use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Remp\MailerModule\Hermes\HermesMessage;
use Remp\MailerModule\Repository;

class HermesTasksRepository extends Repository
{
    const STATE_DONE  = 'done';
    const STATE_ERROR = 'error';

    protected $tableName = 'hermes_tasks';

    public function add(HermesMessage $message, $state)
    {
        list($usec, $sec) = explode(' ', $message->getCreated());
        $timestamp = number_format(((float)$usec + (float)$sec), 6, '.', '');
        $createdAt = DateTime::createFromFormat('U.u', $timestamp)
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        return $this->insert([
            'id' => $message->getId(),
            'type' => $message->getType(),
            'payload' => Json::encode($message->getPayload()),
            'state' => $state,
            'created_at' => $createdAt,
            'processed_at' => new DateTime(),
        ]);
    }

    public function getStateCounts()
    {
        return $this->getTable()->group('state, type')->select('state, type, count(*) AS count')->order('count DESC');
    }
}
