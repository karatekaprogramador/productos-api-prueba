<?php

namespace Tests\Feature;

use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    public function test_documentation_page_and_openapi_contract_cover_api_routes(): void
    {
        $this->get('/api/documentation')->assertOk()->assertSee('swagger-ui')->assertSee('openapi.json');

        $spec = json_decode(file_get_contents(public_path('openapi.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('3.0.3', $spec['openapi']);
        $this->assertSame('bearer', $spec['components']['securitySchemes']['bearerAuth']['scheme']);
        $this->assertSame([], $spec['paths']['/login']['post']['security']);
        foreach ([['/login', 'post'], ['/logout', 'post'], ['/products', 'get'], ['/products', 'post'], ['/products/{id}', 'get'], ['/products/{id}', 'put'], ['/products/{id}', 'delete'], ['/products/{id}/prices', 'get'], ['/products/{id}/prices', 'post']] as [$path, $method]) {
            $this->assertArrayHasKey($method, $spec['paths'][$path]);
        }
    }
}
