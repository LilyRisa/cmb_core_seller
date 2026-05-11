<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_reports_ok_with_component_checks(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.checks.database.status', 'ok')
            ->assertJsonPath('data.checks.cache.status', 'ok')
            // queue runs on `sync` in the test env -> reported as skipped, not critical
            ->assertJsonPath('data.checks.queue.status', 'skipped')
            ->assertJsonStructure(['data' => ['status', 'app', 'env', 'time', 'checks']]);
    }

    public function test_health_endpoint_carries_a_request_id_header(): void
    {
        $this->getJson('/api/v1/health')->assertHeader('X-Request-Id');
    }
}
