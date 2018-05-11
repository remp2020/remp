<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

class PreprocessException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
