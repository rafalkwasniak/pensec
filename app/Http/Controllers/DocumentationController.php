<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves the documents kept in docs/, which lives outside the web root so that
 * the specification, the reference report and the contract sit in one place
 * without the web server handing out whatever else lands there.
 */
class DocumentationController extends Controller
{
    public function contract(): Response
    {
        return $this->file(base_path('docs/OpenAPI/openapi.yaml'), 'application/yaml');
    }

    /**
     * Any .html file dropped into docs/ is served immediately, with no route to
     * add. The slug pattern is enforced by the route, so it can carry neither a
     * slash nor a dot and cannot climb out of the directory.
     */
    public function page(string $slug): Response
    {
        return $this->file(base_path("docs/{$slug}.html"), 'text/html; charset=utf-8');
    }

    private function file(string $path, string $contentType): Response
    {
        if (! is_file($path)) {
            throw new NotFoundHttpException;
        }

        return response(file_get_contents($path), 200, ['Content-Type' => $contentType]);
    }
}
