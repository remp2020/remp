<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

class GeneratorParamsProcessor
{
    /** @var array(ParamInterface) */
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->params as $param) {
            if (!$param->isValid()) {
                $errors[] = $param->getKey();
            }
        }
        return $errors;
    }

    public function getValues(): array
    {
        $result = [];
        foreach ($this->params as $param) {
            $result[$param->getKey()] = $param->getValue();
        }

        return $result;
    }
}
