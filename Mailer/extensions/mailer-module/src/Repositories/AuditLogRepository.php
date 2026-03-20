<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Database\Explorer;
use Nette\Security\UserStorage;

class AuditLogRepository extends Repository
{
    /** @var string */
    protected $tableName = 'audit_logs';

    const OPERATION_CREATE = 'create';
    const OPERATION_READ = 'read';
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';

    public function __construct(Explorer $database, protected UserStorage $userStorage)
    {
        parent::__construct($database);
    }

    public function add(string $operation, string $tableName, string $signature, array $data = [])
    {
        [$isAuthenticated, $identity, $reason] = $this->userStorage->getState();
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
