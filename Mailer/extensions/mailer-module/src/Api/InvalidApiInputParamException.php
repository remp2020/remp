<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api;

use Exception;

class InvalidApiInputParamException extends Exception
{
    private ?string $errorCode;

    public function __construct(string $message = "", int $httpCode = 0, string $errorCode = null)
    {
        parent::__construct($message, $httpCode);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode()
    {
        return $this->errorCode ?? 'invalid_input';
    }
}
