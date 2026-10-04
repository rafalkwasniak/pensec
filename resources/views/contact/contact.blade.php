<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kontakt | Pensec</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- KONTENER NAWIGACJI -->
    <div class="relative z-50 w-full flex justify-center px-4 sm:px-6 mt-6 mb-10">
        
        <!-- NAWIGACJA -->
        <header class="flex min-w-0 max-w-full items-center gap-3 sm:gap-5 rounded-full border border-ink-line bg-ink-raised/70 px-4 sm:px-5 py-2 sm:py-2.5 shadow-lg backdrop-blur-md">
            
            <a href="/" class="group shrink-0 flex items-center hover:opacity-80 transition-opacity">
                <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256" class="theme-when-dark h-4 sm:h-5 w-auto">
                <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="theme-when-light h-4 sm:h-5 w-auto">
            </a>

            <div class="h-4 w-px shrink-0 bg-ink-line"></div>

            <nav class="flex shrink min-w-0 items-center gap-4 sm:gap-5 overflow-x-auto hide-scrollbar">
                <a href="/tests" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium text-muted hover:text-chrome">
                    Specyfikacja
                </a>
                <a href="/contact" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium text-chrome font-semibold">
                    Kontakt
                </a>
            </nav>
            
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>
            
            <div class="shrink-0 flex items-center">
                @include('partials.theme-toggle')
            </div>
            
            <div class="h-4 w-px shrink-0 bg-ink-line"></div>

            <a href="/panel/reports" class="shrink-0 text-xs sm:text-sm font-medium text-muted hover:text-chrome transition-colors whitespace-nowrap">
                Panel
            </a>
            
        </header>
    </div>

    <main class="flex-grow w-full mx-auto max-w-6xl px-6 py-10" style="margin-top: 50px;">

        <!-- GRID -->
        <div class="grid gap-8 md:grid-cols-2">
            
            <!-- KONTAKT -->
            <div class="card p-6 sm:p-8 flex flex-col justify-between border-brand/30 bg-ink-raised/50">
                <div>
                    <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                        <span class="font-mono text-brand text-lg">>_</span>
                        <h3 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">Kontakt</h3>
                    </div>
                    
                    <div class="space-y-6">
                        <div>
                            <span class="font-mono text-xs text-brand block mb-1">EMAIL</span>
                            <a href="mailto:norbert@kwasniak.org" class="text-xl font-medium text-chrome hover:text-brand transition-colors">
                                norbert@kwasniak.org
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- WSPÓŁPRACA -->
            <div class="card p-6 sm:p-8 flex flex-col relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-brand/10 blur-3xl rounded-full"></div>
                <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                    <span class="font-mono text-brand text-lg">>_</span>
                    <h3 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">Współpraca</h3>
                </div>
                
                <ul class="space-y-4 text-sm text-muted flex-grow">
                    <li class="flex gap-3 items-start">
                        <span class="text-brand font-mono font-bold">01</span>
                        <span>Inicjalizacja urządzenia w sieci i wykonanie zautomatyzowanych testów penetracyjnych.</span>
                    </li>
                    <li class="flex gap-3 items-start">
                        <span class="text-brand font-mono font-bold">02</span>
                        <span>Dostarczenie raportu biznesowego oraz technicznego.</span>
                    </li>
                    <li class="flex gap-3 items-start">
                        <span class="text-brand font-mono font-bold">03</span>
                        <span>Naprawa błędów konfiguracyjnych i wprowadzenie zabezpieczeń w infrastrukturze klienta.</span>
                    </li>
                    <li class="flex gap-3 items-start">
                        <span class="text-brand font-mono font-bold">04</span>
                        <span>Przeprowadzenie <strong>bezpłatnego</strong> audytu potwierdzającego neutralizację podatności.</span>
                    </li>
                </ul>

                <div class="pt-6 mt-8 border-t border-ink-line">
                    <p class="text-xs leading-relaxed text-muted">
                        Procedura realizowana jest w oparciu o standard Rules of Engagement (RoE) oraz klauzulę poufności NDA.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <footer class="border-t border-ink-line mt-auto">
    <div class="mx-auto flex max-w-6xl items-center justify-center px-6 py-10 text-sm text-muted">
        <div class="flex items-center gap-1.5">
            <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256" class="theme-when-dark h-4 sm:h-5 w-auto">
            <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="theme-when-light h-4 sm:h-5 w-auto">
            <span class="font-semibold tracking-wide text-chrome">&copy;</span>
            <span class="font-semibold tracking-wide text-chrome">{{ date('Y') }}</span>
        </div>
    </div>
	</footer>

</body>
</html>