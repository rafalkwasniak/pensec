<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $topicTitle }} | Pensec</title>
    <link rel="icon" type="image/png" href="/favicon.png">

    @include('partials.theme-boot')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Full viewport height even on mobile, so the inner <nav> is the only
           thing that scrolls - without top/bottom the fixed panel takes the
           height of its content and the page scrolls behind it instead. */
        .course-sidebar { width: 320px; position: fixed; top: 0; bottom: 0; left: 0; transform: translateX(-100%); z-index: 50; }
        .course-sidebar.sidebar-open { transform: translateX(0) !important; }
        .course-main { padding-left: 0; min-height: 100vh; }

        @media (min-width: 1024px) {
            .course-sidebar { transform: translateX(0) !important; }
            .course-main { padding-left: 320px !important; }
        }
    </style>
</head>
<body class="bg-ink antialiased text-chrome flex flex-col min-h-screen" data-course-slug="{{ $slug }}">

    <!-- Pasek postępu -->
    <div id="scroll-progress" class="fixed top-0 left-0 h-1 bg-brand z-50 transition-all duration-75" style="width: 0%;"></div>

    <!-- MOBILNA BELKA -->
    <div class="lg:hidden sticky top-0 z-40 flex items-center justify-between border-b border-ink-line bg-ink/90 backdrop-blur-md px-6 py-4">
        <a href="/" class="flex items-center hover:opacity-80 transition-opacity">
            <img src="/images/pensec-logo.webp" alt="Pensec" class="theme-when-dark h-5 w-auto">
            <img src="/images/pensec-logo-light.webp" alt="Pensec" class="theme-when-light h-5 w-auto">
        </a>
        <div class="flex items-center gap-5">
            @include('partials.theme-toggle')
            <button id="mobile-menu-btn" class="text-muted hover:text-chrome transition-colors focus:outline-none">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- ZASŁONA MOBILNA -->
    <div id="mobile-overlay" class="fixed inset-0 z-40 bg-ink/80 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300"></div>

    <!-- PANEL BOCZNY -->
    <aside id="sidebar" class="course-sidebar fixed border-r border-ink-line bg-ink flex flex-col transition-transform duration-300 ease-in-out">
        
        <!-- Pasek górny panelu na PC (Rozciągnięty logo-motyw) -->
        <div class="h-20 shrink-0 hidden lg:flex items-center justify-between border-b border-ink-line px-6">
            <a href="/" class="hover:opacity-80 transition-opacity flex items-center">
                <img src="/images/pensec-logo.webp" alt="Pensec" class="theme-when-dark h-5 w-auto">
                <img src="/images/pensec-logo-light.webp" alt="Pensec" class="theme-when-light h-5 w-auto">
            </a>
            @include('partials.theme-toggle')
        </div>

        <nav class="flex-1 overflow-y-auto overscroll-contain px-6 py-8 hide-scrollbar">
            <ul class="space-y-0">
                @foreach($outline as $chapter)
                    <!-- Zlikwidowana podwójna kreska dzięki first:border-t-0 -->
                    <li class="pt-8 mb-8 border-t border-ink-line first:border-t-0 first:pt-0">
                        <div class="mb-5">
                            <h3 class="text-[0.65rem] font-medium uppercase tracking-[0.2em] text-brand">
                                {{ $chapter['kicker'] }}
                            </h3>
                            <p class="text-sm font-medium text-chrome mt-1">{{ $chapter['title'] }}</p>
                        </div>

                        <ul class="space-y-1.5 course-menu-list">
                            @foreach($chapter['files'] as $fileSlug =>$fileTitle)
                                <li>
                                    <a href="/course/{{ $fileSlug }}" data-slug="{{ $fileSlug }}"
                                       class="course-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-all {{ request()->is('course/' . $fileSlug) ? 'bg-ink-raised border border-ink-line text-chrome font-medium' : 'border border-transparent text-muted hover:bg-ink-raised hover:text-chrome' }}">
                                        <span class="status-icon w-4 h-4 shrink-0 flex items-center justify-center"></span>
                                        <span class="truncate">{{ $fileTitle }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach

                <!-- Zakończenie (Pełna spójność ze stylem rozdziałów) -->
                <li class="pt-8 mb-8 border-t border-ink-line">
                    <div class="mb-5">
                        <h3 class="text-[0.65rem] font-medium uppercase tracking-[0.2em] text-brand">
                            Zakończenie
                        </h3>
                        <p class="text-sm font-medium text-chrome mt-1">Egzamin i Certyfikacja</p>
                    </div>
                    
                    <ul class="space-y-1.5">
                        <li>
                            <a href="/course/exam" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-all border border-transparent text-muted hover:bg-ink-raised hover:text-chrome">
                                <span class="w-4 h-4 shrink-0 flex items-center justify-center text-brand">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <span class="truncate">Test końcowy</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- GŁÓWNA ZAWARTOŚĆ -->
    <main class="course-main flex flex-col w-full flex-grow">
        <div class="flex-grow w-full mx-auto max-w-5xl px-6 py-10 lg:py-16">
            
            <!-- TYTUŁ (Konstrukcja 1:1 z Home Page) -->
            <h2 class="text-sm uppercase tracking-[0.25em] text-brand">{{ $chapterKicker }}{{ $chapterTitle ? ' · '.$chapterTitle : '' }}</h2>
            <p class="mt-4 max-w-3xl text-2xl leading-snug chrome-text">
                {{ $topicTitle }}
            </p>

            <!-- KAFELEK Z TREŚCIĄ -->
            <div class="mt-12 card p-6 sm:p-10 lg:p-16 relative">
                
                @if($readingTime)
                <!-- Czas czytania (Został na górze wewnątrz kafelka) -->
                <div class="flex items-center gap-2 text-xs font-mono text-muted mb-8 uppercase tracking-widest border-b border-ink-line pb-4">
                    <svg class="h-4 w-4 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Czas czytania tematu: ok. {{ $readingTime * 2 }} min</span>
                </div>
                @endif

                @if($checkpoint)
                <!-- CHECKPOINT: pytania podsumowujące rozdział (ocena po stronie serwera) -->
                <div id="checkpoint" data-url="{{ route('course.checkpoint', $checkpointChapter) }}">
                    <p class="mb-8 text-base leading-relaxed text-muted">Kilka pytań podsumowujących rozdział. Odpowiedzi ocenia serwer.</p>
                    <div class="space-y-8">
                        @foreach($checkpoint as $qi => $q)
                            <div class="checkpoint-q" data-index="{{ $qi }}">
                                <p class="text-base font-medium text-chrome">{{ $loop->iteration }}. {{ $q['prompt'] }}</p>
                                <div class="mt-4 space-y-2">
                                    @foreach($q['options'] as $oi => $opt)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-md border border-ink-line bg-ink px-4 py-3 text-sm text-muted transition-colors hover:bg-ink-raised">
                                            <input type="radio" name="cq{{ $qi }}" value="{{ $oi }}" class="mt-1 accent-brand">
                                            <span>{{ $opt }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-8 flex items-center gap-4">
                        <button id="checkpoint-submit" class="rounded-md border border-ink-line bg-ink-raised px-6 py-3 text-sm font-medium text-chrome hover:border-brand transition-colors">
                            Sprawdź odpowiedzi
                        </button>
                        <span id="checkpoint-score" class="font-mono text-sm text-muted"></span>
                    </div>
                </div>
                @elseif($bookTopic ?? false)
                <!-- KSIĄŻKA: zajawka na końcu kursu -->
                <article class="pensec-markdown">
                    <p>Ten kurs wyrósł z wieloletniej praktyki z bazami danych. Jeżeli chcesz pogłębić ten obszar, autor kursu przygotował poświęconą mu książkę.</p>
                    <p>Pełny opis, zawartość i okładka znajdują się na osobnej stronie.</p>
                </article>
                <div class="mt-8">
                    <a href="/course/ksiazka" class="inline-block rounded-md border border-ink-line bg-ink-raised px-6 py-3 text-sm font-medium text-chrome transition-colors hover:border-brand">
                        Zobacz książkę
                    </a>
                </div>
                @else
                <!-- TREŚĆ MD -->
                <article class="pensec-markdown">
                    {!! $html !!}
                </article>
                @endif

                <!-- PRZYCISKI -->
                <div class="mt-16 pt-8 border-t border-ink-line flex justify-between items-center w-full">
                    <div>
                        @if($prev)
                            <a href="/course/{{ $prev }}" class="group flex flex-col text-left transition-colors">
                                <span class="text-xs uppercase tracking-[0.2em] text-muted group-hover:text-chrome mb-1">Poprzedni temat</span>
                                <span class="text-sm font-medium text-brand group-hover:text-chrome">← Wróć</span>
                            </a>
                        @endif
                    </div>
                    <div>
                        @if($next)
                            <a href="/course/{{ $next }}" class="group flex flex-col text-right transition-colors">
                                <!-- Dynamiczny tekst z PHP (Następny temat/Następny rozdział) -->
                                <span class="text-xs uppercase tracking-[0.2em] text-muted group-hover:text-chrome mb-1">{{ $nextText }}</span>
                                <span class="text-sm font-medium text-brand group-hover:text-chrome">Przejdź dalej →</span>
                            </a>
                        @else
                            <a href="/course/exam" class="group flex flex-col text-right transition-colors">
                                <!-- Wyczyszczony ze styli pudełkowych, czysty link -->
                                <span class="text-xs uppercase tracking-[0.2em] text-brand mb-1">Koniec materiału</span>
                                <span class="text-sm font-medium text-chrome group-hover:text-brand transition-colors">Rozpocznij egzamin →</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            
        </div>

        <!-- TWOJA ORYGINALNA STOPKA -->
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
    </main>

    {{-- Logika strony (menu, pasek postępu, kopiowanie kodu, pamięć postępu)
         mieszka w resources/js/app.js i jest ładowana przez Vite w <head>. --}}

    @include('partials.cookie-consent')
</body>
</html>