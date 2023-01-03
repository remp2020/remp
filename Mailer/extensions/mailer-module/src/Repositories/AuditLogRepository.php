<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Database\Explorer;
use Nette\Security\IUserStorage;

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

    public function __construct(Explorer $database, IUserStorage $userStorage)
    {
        parent::__construct($database);
        $this->database = $database;
        $this->userStorage = $userStorage;
    }

    public function add(string $operation, string $tableName, string $signature, array $data = [])
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
