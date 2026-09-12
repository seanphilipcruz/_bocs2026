<?php

namespace Tests\Feature;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ApiJsonResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::prefix('api/testing')->group(function (): void {
            Route::get('/success', fn () => response()->json([
                'status' => 'success',
                'data' => ['component' => 'vuetify-card'],
            ]));

            Route::get('/failure', function (): never {
                throw new RuntimeException('Sensitive implementation detail');
            });

            Route::get('/passport-failure', function (): never {
                throw new RuntimeException('Key file does not exist or is not readable');
            });
        });
    }

    public function test_successful_api_response_is_json(): void
    {
        $this->getJson('/api/testing/success')
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertExactJson(['status' => 'success', 'data' => ['component' => 'vuetify-card']]);
    }

    public function test_validation_errors_use_the_component_error_contract(): void
    {
        $this->postJson('/api/login')
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure(['status', 'code', 'message', 'errors' => ['email', 'password']]);
    }

    public function test_controller_validation_errors_use_the_same_json_contract(): void
    {
        $this->withoutMiddleware(Authenticate::class)
            ->postJson('/api/advertisers/store')
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_unauthenticated_errors_are_json(): void
    {
        $this->getJson('/api/advertisers')
            ->assertUnauthorized()
            ->assertJson(['status' => 'error', 'code' => 'unauthenticated', 'message' => 'Unauthenticated.']);
    }

    public function test_missing_api_routes_are_json(): void
    {
        $this->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('code', 'not_found')
            ->assertJsonStructure(['status', 'code', 'message']);
    }

    public function test_method_not_allowed_errors_are_json(): void
    {
        $this->putJson('/api/login')
            ->assertStatus(405)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('code', 'method_not_allowed')
            ->assertJsonStructure(['status', 'code', 'message']);
    }

    public function test_unexpected_errors_return_safe_actionable_json_details(): void
    {
        $response = $this->getJson('/api/testing/failure')
            ->assertServerError()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('code', 'server_error')
            ->assertJsonPath('message', 'The API could not complete the request.')
            ->assertJsonPath('error.category', 'application_error')
            ->assertJsonPath('error.method', 'GET')
            ->assertJsonPath('error.path', 'api/testing/failure')
            ->assertJsonStructure(['error' => ['category', 'request_id', 'method', 'path', 'suggestion']]);

        $this->assertStringNotContainsString('Sensitive implementation detail', $response->getContent());
        $this->assertSame($response->headers->get('X-Request-ID'), $response->json('error.request_id'));
    }

    public function test_debug_mode_includes_the_exception_details_and_trimmed_trace(): void
    {
        config(['app.debug' => true]);

        $this->getJson('/api/testing/failure')
            ->assertServerError()
            ->assertJsonPath('error.debug.exception', RuntimeException::class)
            ->assertJsonPath('error.debug.message', 'Sensitive implementation detail')
            ->assertJsonStructure(['error' => ['debug' => ['exception', 'message', 'file', 'line', 'trace']]]);
    }

    public function test_passport_failures_receive_specific_safe_guidance(): void
    {
        $this->getJson('/api/testing/passport-failure')
            ->assertServerError()
            ->assertJsonPath('error.category', 'authentication_configuration_error')
            ->assertJsonPath('message', 'The authentication service is not configured correctly.')
            ->assertJsonPath(
                'error.suggestion',
                'Verify that the Laravel Passport keys exist, are readable, and have valid permissions.',
            );
    }
}
