<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_the_sitemap_is_served_as_xml_and_lists_public_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('content-type'));

        $body = $response->getContent();
        $this->assertStringContainsString('<urlset', $body);
        $this->assertStringContainsString(url('/'), $body);
        $this->assertStringContainsString(url('/course'), $body);

        // At least one real course lesson from the manifest is listed.
        $this->assertStringContainsString(url('/course/00-wprowadzenie/01'), $body);

        // The privacy policy is a public page and belongs in the map.
        $this->assertStringContainsString(url('/polityka-prywatnosci'), $body);
    }

    public function test_the_sitemap_leaves_out_the_panel_and_the_api(): void
    {
        $body = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('/panel', $body);
        $this->assertStringNotContainsString('/api/', $body);
    }

    public function test_robots_allows_crawling_and_points_at_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://pensec.top/sitemap.xml', $robots);
        $this->assertStringNotContainsString('Disallow: /', $robots);
    }
}
