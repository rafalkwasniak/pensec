<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocumentationTest extends TestCase
{
    public function test_the_contract_is_public(): void
    {
        $response = $this->get('/api/openapi.yaml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/yaml');

        $this->assertStringContainsString('openapi: 3.0.3', $response->getContent());
        $this->assertStringContainsString('/api/v1/reports', $response->getContent());
    }

    public function test_documents_are_public(): void
    {
        $this->get('/docs/api-guide.html')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee('Submitting a scan report');

        $this->get('/docs/api.html')->assertOk();
    }

    public function test_an_unknown_document_is_not_found(): void
    {
        $this->get('/docs/nie-ma-takiego.html')->assertNotFound();
    }

    /**
     * The slug pattern is the whole defence here, so it is worth pinning down:
     * nothing outside docs/ may be reachable, and nothing but .html may be served.
     */
    public function test_it_refuses_to_serve_anything_but_html_from_the_directory(): void
    {
        $this->get('/docs/specyfikacja.md')->assertNotFound();
        $this->get('/docs/raport.json')->assertNotFound();
        $this->get('/docs/OpenAPI/openapi.yaml')->assertNotFound();
        $this->get('/docs/OpenAPI/spectral.yaml')->assertNotFound();
    }

    public function test_it_cannot_be_walked_out_of_the_directory(): void
    {
        foreach ([
            '/docs/../.env.html',
            '/docs/..%2F..%2F.env.html',
            '/docs/....//....//.env.html',
            '/docs/%2e%2e%2f.env.html',
            '/docs/config/app.html',
        ] as $attempt) {
            $this->get($attempt)->assertNotFound();
        }
    }
}
