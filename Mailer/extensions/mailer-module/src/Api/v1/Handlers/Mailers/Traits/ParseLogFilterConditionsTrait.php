<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers\Traits;

use Exception;
use Nette\Utils\DateTime;

trait ParseLogFilterConditionsTrait
{
    /**
     * @param string $field
     * @param array $filter
     * @return array of conditions. Each element of array can be used in Nette's selection where($element...) call
     * @throws \Exception
     * @example
     *
     * [
     *     "from" => "2020-01-01T00:00:00Z",
     *     "to" => "2020-04-05T00:00:00Z",
     * ],
     */
    protected function parseConditions(string $field, array $filter): array
    {
        $conditions = [];

        $availableColumns = [
            "sent_at" => "created_at",
            "delivered_at" => "delivered_at",
            "clicked_at" => "clicked_at",
            "opened_at" => "opened_at",
            "dropped_at" => "dropped_at",
            "spam_complained_at" => "spam_complained_at",
            "hard_bounced_at" => "hard_bounced_at"
        ];

        if (!array_key_exists($field, $availableColumns)) {
            throw new Exception("Property $field is not allowed in log filter.");
        }
        $column = $availableColumns[$field];

        // key is column, value is date
        if (isset($filter['from'])) {
            $conditions[] = ["$column >= ?", DateTime::from($filter['from'])];
        }
        if (isset($filter['to'])) {
            $conditions[] = ["$column < ?", DateTime::from($filter['to'])];
        }

        $conditions[] = ["$column IS NOT NULL"];
        return $conditions;
    }
}
