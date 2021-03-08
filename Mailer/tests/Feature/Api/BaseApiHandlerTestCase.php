<?php
declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\Feature\BaseFeatureTestCase;
use Tomaj\NetteApi\ApiDecider;
use Tomaj\NetteApi\Handlers\BaseHandler;

abstract class BaseApiHandlerTestCase extends BaseFeatureTestCase
{
    protected function getHandler(string $className): BaseHandler
    {
        $apiDecidier = $this->inject(ApiDecider::class);

        $apis =  $apiDecidier->getApis();
        foreach ($apis as $api) {
            $handler = $api->getHandler();
            if (get_class($handler) == $className) {
                return $handler;
            }
        }
        throw new \Exception("Cannot find api handler '$className'");
    }
}
