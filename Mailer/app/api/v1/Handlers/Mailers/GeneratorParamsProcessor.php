<?php

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

class GeneratorParamsProcessor
{
    /** @var array(ParamInterface) */
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function getErrors()
    {
        $errors = [];
        foreach ($this->params as $param) {
            if (!$param->isValid()) {
                $errors[] = $param->getKey();
            }
        }
        return $errors;
    }

    public function getValues()
    {
        $result = [];
        foreach ($this->params as $param) {
            $result[$param->getKey()] = $param->getValue();
        }

        return $result;
    }
}
