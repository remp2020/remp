<?php

namespace Remp\MailerModule\Hermes;

class LogFilter
{
    public static function add($filters)
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
