<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Baza wiedzy o bezpieczeństwie sieci | Pensec</title>
    <meta name="description" content="Artykuły o audycie i bezpieczeństwie sieci wewnętrznej: koszt audytu, obowiązki NIS2, wewnętrzny test penetracyjny, bezpieczeństwo sieci przemysłowej OT, bezpieczeństwo i poufność badania.">
    @include('partials.og', ['title' => 'Baza wiedzy o bezpieczeństwie sieci | Pensec', 'description' => 'Artykuły o audycie i bezpieczeństwie sieci wewnętrznej: koszt audytu, obowiązki NIS2, wewnętrzny test penetracyjny, bezpieczeństwo sieci przemysłowej OT, bezpieczeństwo i poufność badania.'])
    <link rel="canonical" href="{{ url('/wiedza') }}">
    <link rel="icon" type="image/png" href="/favicon.png">
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

    <main class="flex-grow w-full mx-auto max-w-4xl px-6 py-10" style="margin-top: 30px;">
        <p class="text-sm uppercase tracking-[0.25em] text-brand">Baza wiedzy</p>
        <h1 class="mt-4 text-3xl font-semibold chrome-text sm:text-4xl">Audyt i bezpieczeństwo sieci</h1>
        <p class="mt-5 max-w-2xl text-base leading-relaxed text-muted">
            Opisy zakresu, przebiegu, kosztu i wyników badania bezpieczeństwa sieci wewnętrznej oraz jego związku z wymaganiami dyrektywy NIS2.
        </p>

        @if ($articles === [])
            <p class="mt-10 text-sm text-muted">Wkrótce pojawią się tu pierwsze artykuły.</p>
        @else
            <div class="mt-10 space-y-4">
                @foreach ($articles as $article)
                    <a href="{{ url('/wiedza/'.$article['slug']) }}" class="block card p-6 transition-colors hover:border-brand">
                        <h2 class="text-lg font-semibold text-chrome">{{ $article['title'] }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $article['description'] }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </main>

    @include('partials.site-footer')

    @include('partials.cookie-consent')
</body>
</html>
