<?php

declare(strict_types=1);

namespace Tests\Smoke;

use Tests\TestCase;

class HealthSmokeTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $this->get('/up')->assertOk();
    }
}
