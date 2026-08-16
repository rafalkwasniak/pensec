<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * The panel speaks Polish to its administrator while the API keeps answering
 * devices in English, so the locale is switched for panel routes only.
 */
class SetPanelLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale('pl');

        return $next($request);
    }
}
