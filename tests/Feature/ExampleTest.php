<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Health-Check ohne CMS-Daten / Home-View (robuster Smoke-Test als GET /).
     */
    public function test_health_endpoint_returns_successful_response(): void
    {
        $this->get('/up')->assertSuccessful();
    }
}
