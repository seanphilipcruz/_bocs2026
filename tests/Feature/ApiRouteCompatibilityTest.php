<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiRouteCompatibilityTest extends TestCase
{
    public function test_legacy_api_route_surface_is_registered(): void
    {
        $expectedRoutes = [
            'authentication.login',
            'authentication.logout',
            'home',
            'advertiser.index',
            'agency.index',
            'contract.index',
            'sale.index',
            'sales.report',
            'employee.index',
            'job.index',
            'log.index',
        ];

        foreach ($expectedRoutes as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing API route [{$routeName}].");
        }
    }

    public function test_protected_api_route_requires_authentication(): void
    {
        $this->getJson('/api/advertisers')->assertUnauthorized();
    }

    public function test_login_validation_contract_is_preserved(): void
    {
        $this->postJson('/api/login', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
