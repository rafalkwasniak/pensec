<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

/**
 * The public URL map for crawlers. Built from the same course manifest the site
 * is built from (resources/course/structure.json), so a lesson added there
 * appears here without a second list to keep in step. Only client-facing pages
 * are listed; the panel is noindex and the API is not for search.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            route('home'),
            route('tests'),
            route('contact'),
            route('course'),
            route('course.book'),
            route('course.exam'),
            route('process'),
            route('wiedza.index'),
            route('privacy'),
        ];

        $manifestPath = resource_path('course/structure.json');

        if (File::exists($manifestPath)) {
            $manifest = json_decode(File::get($manifestPath), true);

            foreach ($manifest['chapters'] ?? [] as $chapter) {
                $dir = $chapter['dir'] ?? null;

                if (! is_string($dir)) {
                    continue;
                }

                // Same guard the controller uses: a lesson is real only when the
                // manifest lists it and its file exists.
                foreach ($chapter['lessons'] ?? [] as $file => $title) {
                    if (File::exists(resource_path("course/{$dir}/{$file}.md"))) {
                        $urls[] = url("/course/{$dir}/{$file}");
                    }
                }
            }
        }

        $articlesPath = resource_path('articles/articles.json');

        if (File::exists($articlesPath)) {
            $articles = json_decode(File::get($articlesPath), true);

            foreach ($articles['articles'] ?? [] as $article) {
                if (isset($article['slug']) && File::exists(resource_path("articles/{$article['slug']}.md"))) {
                    $urls[] = url('/wiedza/'.$article['slug']);
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.htmlspecialchars($url, ENT_XML1).'</loc></url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
