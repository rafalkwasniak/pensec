<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_the_public_pages_render(): void
    {
        foreach (['/', '/tests', '/contact', '/course', '/jak-przebiega-audyt', '/wiedza', '/polityka-prywatnosci'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_the_footer_links_the_key_pages_on_every_public_page(): void
    {
        foreach (['/', '/course', '/wiedza'] as $path) {
            $this->get($path)
                ->assertSee('href="/wiedza"', false)
                ->assertSee('href="/jak-przebiega-audyt"', false)
                ->assertSee('href="/polityka-prywatnosci"', false);
        }
    }

    public function test_the_privacy_policy_renders_with_its_heading(): void
    {
        $this->get('/polityka-prywatnosci')
            ->assertOk()
            ->assertSee('Polityka prywatności')
            ->assertSee('rel="canonical"', false);
    }

    public function test_the_home_and_course_pages_carry_structured_data(): void
    {
        $this->get('/')->assertSee('application/ld+json', false)->assertSee('"@type":"Organization"', false);
        $this->get('/course')->assertSee('"@type":"Course"', false);
    }

    public function test_every_public_page_has_a_canonical_and_a_heading(): void
    {
        $this->get('/')->assertSee('rel="canonical"', false);

        // The two pages that had no visible title now carry a heading for crawlers.
        $this->get('/tests')->assertSee('rel="canonical"', false)->assertSee('Specyfikacja silnika audytu');
        $this->get('/contact')->assertSee('rel="canonical"', false)->assertSee('Kontakt i zamówienie audytu bezpieczeństwa sieci');
    }

    public function test_the_retired_faq_redirects_to_the_knowledge_base(): void
    {
        $this->get('/faq')->assertStatus(301)->assertRedirect('/wiedza');
    }

    public function test_the_subpages_carry_an_open_graph_card(): void
    {
        foreach (['/tests', '/contact', '/course', '/jak-przebiega-audyt', '/wiedza', '/polityka-prywatnosci'] as $path) {
            $this->get($path)
                ->assertSee('property="og:title"', false)
                ->assertSee('pensec-logo-print.png', false);
        }
    }
}
