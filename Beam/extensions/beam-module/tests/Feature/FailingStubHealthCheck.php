<?php

namespace Remp\BeamModule\Tests\Feature;

use UKFast\HealthCheck\HealthCheck;
use UKFast\HealthCheck\Status;

class FailingStubHealthCheck extends HealthCheck
{
    protected string $name = 'stub';

    public function status(): Status
    {
        return $this->problem('Stub check failed', [
            'exception' => [
                'error' => 'file_put_contents(/secret/path/to/storage): Permission denied',
                'class' => \ErrorException::class,
                'line' => 135,
                'file' => '/secret/path/to/file.php',
                'trace' => ['#0 /secret/path/to/vendor/Local.php(135)'],
            ],
        ]);
    }
}
