@php
    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article['title'],
        'description' => $article['description'],
        'datePublished' => $article['date'] ?? null,
        'inLanguage' => 'pl',
        'author' => ['@type' => 'Organization', 'name' => 'Pensec'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Pensec', 'url' => url('/')],
        'mainEntityOfPage' => url('/wiedza/'.$article['slug']),
    ];
@endphp
<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $article['title'] }} | Pensec</title>
    <meta name="description" content="{{ $article['description'] }}">
    @include('partials.og', ['title' => $article['title'].' | Pensec', 'description' => $article['description'], 'type' => 'article'])
    <link rel="canonical" href="{{ url('/wiedza/'.$article['slug']) }}">
    <link rel="icon" type="image/png" href="/favicon.png">
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex flex-col antialiased">

    <div class="relative z-50 w-full flex justify-center px-4 sm:px-6 mt-6 mb-10">
        <header class="flex min-w-0 max-w-full items-center gap-3 sm:gap-5 rounded-full border border-ink-line bg-ink-raised/70 px-4 sm:px-5 py-2 sm:py-2.5 shadow-lg backdrop-blur-md">
            <a href="/" class="group shrink-0 flex items-center hover:opacity-80 transition-opacity">
                <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256" class="theme-when-dark h-4 sm:h-5 w-auto">
                <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="theme-when-light h-4 sm:h-5 w-auto">
            </a>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <nav class="flex shrink min-w-0 items-center gap-4 sm:gap-5 overflow-x-auto hide-scrollbar">
                <a href="/tests" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium text-muted hover:text-chrome">Specyfikacja</a>
                <a href="/course" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium text-muted hover:text-chrome">Kurs</a>
                <a href="/contact" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium text-muted hover:text-chrome">Kontakt</a>
            </nav>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <div class="shrink-0 flex items-center">@include('partials.theme-toggle')</div>
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            <a href="/panel/reports" class="shrink-0 text-xs sm:text-sm font-medium text-muted hover:text-chrome transition-colors whitespace-nowrap">Panel</a>
        </header>
    </div>

    <main class="flex-grow w-full mx-auto max-w-3xl px-6 py-10" style="margin-top: 30px;">
        <a href="/wiedza" class="text-sm text-muted hover:text-chrome">&larr; Baza wiedzy</a>

        <article class="mt-6">
            <h1 class="text-3xl font-semibold chrome-text sm:text-4xl">{{ $article['title'] }}</h1>
            <p class="mt-4 text-base leading-relaxed text-muted">{{ $article['description'] }}</p>

            <div class="pensec-markdown mt-8 text-chrome">
                {!! $html !!}
            </div>
        </article>

        <div class="mt-12 flex justify-center border-t border-ink-line pt-6">
            <a href="/contact" class="inline-block rounded-md border border-ink-line bg-ink-raised px-6 py-2.5 text-sm font-medium text-chrome hover:border-brand transition-colors">
                Zamów audyt
            </a>
        </div>
    </main>

    @include('partials.site-footer')

    @include('partials.cookie-consent')
</body>
</html>
