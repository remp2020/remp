<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers\Traits;

use Nette\Utils\DateTime;

trait ParseLogFilterConditionsTrait
{
    /**
     * @param array $filter
     * @return array of conditions. Each element of array can be used in Nette's selection where($element...) call
     * @throws \Exception
     * @example
     *
     * ["delivered_at", "clicked_at", "opened_at"....]
     *
     * or
     *
     * [
     *      "delivered_at" => [
     *          "from" => "2020-01-01T00:00:00Z",
     *          "to" => "2020-04-05T00:00:00Z",
     *      ],
     *      "clicked_at" => [
     *          "from" => "2020-01-01T00:00:00Z",
     *          "to" => "2020-04-05T00:00:00Z",
     *      ],
     * ]
     */
    public function parseConditions(array $filter): array
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

        foreach ($filter as $key => $value) {
            $field = is_array($value) ? $key : $value;
            if (!array_key_exists($key, $availableColumns)) {
                throw new \Exception("Property $field is not allowed in log filter.");
            }
            $column = $availableColumns[$field];

            if (is_array($value)) {
                // key is column, value is date
                if (isset($value['from'])) {
                    $conditions[] = ["$column >= ?", DateTime::from($value['from'])];
                }
                if (isset($value['to'])) {
                    $conditions[] = ["$column < ?", DateTime::from($value['to'])];
                }
                continue;
            }

            $conditions[] = ["$column IS NOT NULL"];
        }

        return $conditions;
    }
}
