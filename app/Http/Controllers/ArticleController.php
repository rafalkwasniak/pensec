<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The knowledge base. Articles are markdown files in resources/articles/, named
 * and ordered by articles.json - the slug is never used to build a path beyond
 * the manifest, and the route admits only [a-z0-9-], so there is no traversal.
 * Markdown is rendered with the same safe options as the course.
 */
class ArticleController extends Controller
{
    public function index(): View
    {
        return view('pages.wiedza-index', ['articles' => $this->manifest()]);
    }

    public function show(string $slug): View
    {
        $article = collect($this->manifest())->firstWhere('slug', $slug);

        abort_if($article === null, 404);

        $html = Str::markdown(File::get(resource_path("articles/{$slug}.md")), [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        return view('pages.wiedza-article', [
            'article' => $article,
            'html' => $html,
        ]);
    }

    /**
     * Listed articles whose file exists. A manifest entry without its .md is
     * silently skipped, so a half-added article is never reachable.
     *
     * @return list<array<string, string>>
     */
    private function manifest(): array
    {
        $path = resource_path('articles/articles.json');

        if (! File::exists($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);
        $articles = [];

        foreach ($data['articles'] ?? [] as $article) {
            if (isset($article['slug']) && File::exists(resource_path("articles/{$article['slug']}.md"))) {
                $articles[] = $article;
            }
        }

        return $articles;
    }
}
