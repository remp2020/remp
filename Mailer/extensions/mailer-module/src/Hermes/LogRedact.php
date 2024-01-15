<?php
declare(strict_types=1);

namespace Remp\MailerModule\Hermes;

use Monolog\LogRecord;

class LogRedact
{
    public static function add(array $filters): callable
    {
        return function (LogRecord $record) use ($filters) {
            $context = $record->context;

            foreach ($filters as $filter) {
                if (isset($context['payload'][$filter])) {
                    $context['payload'][$filter] = '******';
                }
                if (isset($context['payload']['params'][$filter])) {
                    $context['payload']['params'][$filter] = '******';
                }
            }
            return $record->with(context: $context);
        };
    }
}
