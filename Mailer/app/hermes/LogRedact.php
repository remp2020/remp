<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

class LogRedact
{
    public static function add(array $filters): callable
    {
        return function ($record) use ($filters) {
            foreach ($filters as $filter) {
                if (isset($record['context']['payload'][$filter])) {
                    $record['context']['payload'][$filter] = '******';
                }
                if (isset($record['context']['payload']['params'][$filter])) {
                    $record['context']['payload']['params'][$filter] = '******';
                }
            }
            return $record;
        };
    }
}
