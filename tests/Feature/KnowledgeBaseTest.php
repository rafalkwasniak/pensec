<?php

namespace Tests\Feature;

use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    public function test_the_index_lists_the_articles(): void
    {
        $this->get('/wiedza')
            ->assertOk()
            ->assertSee('Ile kosztuje audyt bezpieczeństwa sieci')
            ->assertSee('Obowiązki NIS2 w praktyce');
    }

    public function test_an_article_renders_with_its_content_and_structured_data(): void
    {
        $this->get('/wiedza/czym-jest-wewnetrzny-test-penetracyjny')
            ->assertOk()
            ->assertSee('Czym jest wewnętrzny test penetracyjny')
            ->assertSee('assume breach')
            ->assertSee('"@type":"Article"', false)
            ->assertSee('pensec-markdown', false);
    }

    public function test_an_unknown_article_is_not_found(): void
    {
        $this->get('/wiedza/nie-ma-takiego-artykulu')->assertNotFound();
    }

    public function test_articles_are_in_the_sitemap(): void
    {
        $body = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString(url('/wiedza'), $body);
        $this->assertStringContainsString(url('/wiedza/obowiazki-nis2-w-praktyce'), $body);
    }
}
