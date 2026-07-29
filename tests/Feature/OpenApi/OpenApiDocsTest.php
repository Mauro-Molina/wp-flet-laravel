<?php

namespace Tests\Feature\OpenApi;

use Tests\TestCase;

class OpenApiDocsTest extends TestCase
{
    public function test_openapi_json_is_available_in_testing(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonStructure([
                'openapi',
                'info' => ['title', 'version'],
                'paths',
            ]);

        $this->assertStringContainsString('1.0.0', (string) $response->json('info.version'));
    }

    public function test_openapi_includes_auth_and_sites_paths(): void
    {
        $paths = $this->getJson('/docs/api.json')->json('paths');

        $this->assertIsArray($paths);
        $pathKeys = array_keys($paths);
        $joined = implode(' ', $pathKeys);

        $this->assertTrue(
            str_contains($joined, 'auth/login') || str_contains($joined, '/auth/login'),
            'OpenAPI should document auth/login'
        );
        $this->assertTrue(
            str_contains($joined, 'sites') || str_contains($joined, '/sites'),
            'OpenAPI should document sites'
        );
    }
}
