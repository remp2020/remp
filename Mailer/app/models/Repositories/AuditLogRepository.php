<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Context;
use Nette\Security\IUserStorage;
use Remp\MailerModule\Repository;

class AuditLogRepository extends Repository
{
    /** @var string */
    protected $tableName = 'audit_logs';

    /** @var IUserStorage */
    protected $userStorage;

    const OPERATION_CREATE = 'create';
    const OPERATION_READ = 'read';
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';

    public function __construct(Context $database, IUserStorage $userStorage)
    {
        parent::__construct($database);
        $this->database = $database;
        $this->userStorage = $userStorage;
    }

    public function add($operation, $tableName, $signature, $data = [])
    {
        $identity = $this->userStorage->getIdentity();
        $userId = $identity ? $identity->getId() : null;

        return $this->insert([
            'operation' => $operation,
            'user_id' => $userId,
            'table_name' => $tableName,
            'signature' => $signature,
            'data' => json_encode($data),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
