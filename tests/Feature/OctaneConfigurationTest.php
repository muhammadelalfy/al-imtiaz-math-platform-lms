<?php

namespace Tests\Feature;

use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Tests\TestCase;

class OctaneConfigurationTest extends TestCase
{
    public function test_optional_octane_runtime_uses_frankenphp_and_request_state_safeguards(): void
    {
        /** @var array<class-string, list<class-string>> $listeners */
        $listeners = config('octane.listeners');

        $this->assertSame('frankenphp', config('octane.server'));
        $this->assertSame(30, config('octane.max_execution_time'));
        $this->assertArrayHasKey(RequestReceived::class, $listeners);
        $this->assertNotEmpty($listeners[RequestReceived::class]);
        $this->assertArrayHasKey(WorkerErrorOccurred::class, $listeners);
        $this->assertNotEmpty($listeners[WorkerErrorOccurred::class]);
    }
}
