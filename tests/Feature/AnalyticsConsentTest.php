<?php

namespace Tests\Feature;

use Tests\TestCase;

class AnalyticsConsentTest extends TestCase
{
    public function test_without_an_id_the_banner_offers_necessary_cookies_only(): void
    {
        config(['pensec.analytics.ga_id' => null]);

        $this->get('/')
            ->assertOk()
            ->assertSee('var GA_ID = null;', false)
            ->assertSee('Akceptuję')
            ->assertDontSee('Tylko niezbędne')
            ->assertDontSee('Ustawienia cookie');

        $this->get('/polityka-prywatnosci')->assertSee('Strona nie stosuje śledzenia');
    }

    public function test_with_an_id_the_choice_is_offered_and_the_tag_is_never_in_the_markup(): void
    {
        config(['pensec.analytics.ga_id' => 'G-ABC123XYZ']);

        $this->get('/')
            ->assertOk()
            ->assertSee('var GA_ID = "G-ABC123XYZ";', false)
            ->assertSee('Tylko niezbędne')
            ->assertSee('Akceptuję wszystkie')
            ->assertSee('data-cookie-settings', false)
            // The loader builds the tag in JavaScript after consent; the page
            // itself never carries it.
            ->assertDontSee('<script async src="https://www.googletagmanager.com', false);

        $this->get('/polityka-prywatnosci')->assertSee('Google Analytics 4');
    }

    public function test_a_malformed_id_is_treated_as_no_analytics(): void
    {
        config(['pensec.analytics.ga_id' => '"></script><script>alert(1)</script>']);

        $this->get('/')->assertOk()->assertSee('var GA_ID = null;', false);
    }

    public function test_the_portal_never_loads_analytics(): void
    {
        config(['pensec.analytics.ga_id' => 'G-ABC123XYZ']);

        $this->get('/portal/login')->assertOk()->assertSee('var GA_ID = null;', false);
    }
}
