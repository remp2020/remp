<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers\Traits;

use Nette\Utils\DateTime;

trait ParseLogFilterConditionsTrait
{
    /**
     * @param array $filter
     * @return array
     * @throws \Exception
     * @example
     * ["delivered_at", "clicked_at", "opened_at"....]
     * or
     * ["delivered_at" => ["from" => "2020-01-01", "to" => "2020-04-05"], "clicked_at" => ["from" => "2020-01-01", "to" => "2020-04-05"]]
     */
    public function parseConditions(array $filter)
    {

        $conditions = [];

        $availableColumns = ["delivered_at", "clicked_at", "opened_at", "dropped_at", "spam_complained_at", "hard_bounced_at"];

        // DB column can be either key in case of array input or value in case of object input

        foreach ($filter as $key => $value) {
            if (is_array($value) && in_array($key, $availableColumns)) {
                if (isset($value['from']) && isset($value['to'])) {
                    $from = DateTime::from($value['from']);
                    $to = DateTime::from($value['to']);

                    $conditions["$key BETWEEN ? AND ?"] = [$from, $to];
                } elseif (isset($value['from'])) {
                    $from = DateTime::from($value['from']);

                    $conditions["$key >="] = $from;
                } elseif (isset($value['to'])) {
                    $to = DateTime::from($value['to']);

                    $conditions["$key <="] = $to;
                } else {
                    $conditions["$key NOT"] = null;
                }
            } elseif (in_array($value, $availableColumns)) {
                $conditions["$value NOT"] = null;
            } else {
                $column = is_array($value) ? $key : $value;
                throw new \Exception
                ("Column $column is not allowed in log filter.");
            }
        }

        return $conditions;
    }
}
