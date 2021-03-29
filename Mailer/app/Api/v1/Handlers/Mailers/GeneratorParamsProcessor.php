<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Tomaj\NetteApi\Params\ParamInterface;

class GeneratorParamsProcessor
{
    /** @var ParamInterface[] */
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->params as $param) {
            $result = $param->validate();
            if (!$result->isOk()) {
                foreach ($result->getErrors() as $error) {
                    $errors[] = sprintf("%s: %s", $param->getKey(), $error);
                }
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
