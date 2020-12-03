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
        $handlers =  $apiDecidier->getHandlers();
        foreach ($handlers as $handler) {
            if (get_class($handler['handler']) == $className) {
                return $handler['handler'];
            }
        }
        throw new \Exception("Cannot find api handler '$className'");
    }
}
