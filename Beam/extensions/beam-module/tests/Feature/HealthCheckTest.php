<?php

namespace Remp\BeamModule\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Remp\BeamModule\Tests\TestCase;

class HealthCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['healthcheck.checks' => [FailingStubHealthCheck::class]]);
    }

    public function testContextOfFailedCheckIsHiddenAndLoggedWhenDebugIsOff()
    {
        config(['app.debug' => false]);
        Log::spy();

        $this->getJson('/health')
            ->assertStatus(500)
            ->assertJsonPath('stub.message', 'Stub check failed')
            ->assertJsonMissingPath('stub.context');

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $context['stub']['exception']['file'] === '/secret/path/to/file.php';
            });
    }

    public function testContextOfFailedCheckIsExposedWhenDebugIsOn()
    {
        config(['app.debug' => true]);

        $this->getJson('/health')
            ->assertStatus(500)
            ->assertJsonPath('stub.context.exception.file', '/secret/path/to/file.php');
    }
}
