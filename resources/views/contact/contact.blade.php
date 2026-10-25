<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kontakt i zamówienie audytu bezpieczeństwa sieci | Pensec</title>
    <meta name="description" content="Formularz zamówienia audytu bezpieczeństwa sieci wewnętrznej firmy lub zakładu przemysłowego oraz zadawania pytań o zakres badania.">
    @include('partials.og', ['title' => 'Kontakt i zamówienie audytu bezpieczeństwa sieci | Pensec', 'description' => 'Formularz zamówienia audytu bezpieczeństwa sieci wewnętrznej firmy lub zakładu przemysłowego oraz zadawania pytań o zakres badania.'])
    <link rel="canonical" href="{{ url('/contact') }}">
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
                <a href="/course" class="shrink-0 transition-colors whitespace-nowrap text-xs sm:text-sm font-medium {{ request()->is('course*') ? 'text-chrome font-semibold' : 'text-muted hover:text-chrome' }}">
					Kurs
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
        <h1 style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">Kontakt i zamówienie audytu bezpieczeństwa sieci</h1>

        <!-- FORMULARZ ZAPYTANIA -->
        <section class="mx-auto max-w-3xl">
            <div class="card p-6 sm:p-8">
                <div class="flex items-center gap-3 border-b border-ink-line pb-4 mb-6">
                    <span class="font-mono text-brand text-lg">>_</span>
                    <h2 class="text-sm uppercase tracking-[0.25em] text-chrome font-semibold">Zamówienie audytu lub pytanie</h2>
                </div>

                <p class="mb-6 text-sm leading-relaxed text-muted">
                    Zapytanie trafia bezpośrednio do zespołu prowadzącego badania. Opis zakresu, kosztu
                    i przebiegu badania zawiera <a href="/wiedza" class="text-brand hover:underline">baza wiedzy</a>,
                    a kolejne etapy współpracy strona <a href="/jak-przebiega-audyt" class="text-brand hover:underline">przebiegu audytu</a>.
                </p>

                @if (session('lead_status') === 'ok')
                    <div class="mb-6 rounded-lg border border-brand/40 bg-brand/10 px-4 py-3 text-sm text-chrome">
                        Zapytanie zostało przyjęte. Odpowiedź zostanie wysłana na podany adres e-mail.
                    </div>
                    {{-- Read by the analytics loader in partials/cookie-consent, only after consent. --}}
                    <script>window.pensecLeadSent = true;</script>
                @endif

                <form method="POST" action="{{ route('contact.lead') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf

                    {{-- Honeypot: ukryte pole, którego człowiek nie wypełni. --}}
                    <div style="position:absolute;left:-9999px" aria-hidden="true">
                        <label>Zostaw to pole puste
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                        </label>
                    </div>

                    <div>
                        <label for="name" class="block text-xs text-muted mb-1">Imię i nazwisko</label>
                        <input id="name" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-sm text-chrome outline-none focus:border-brand">
                        @error('name') <p class="mt-1 text-xs text-warn">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs text-muted mb-1">E-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                               class="w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-sm text-chrome outline-none focus:border-brand">
                        @error('email') <p class="mt-1 text-xs text-warn">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="company" class="block text-xs text-muted mb-1">Firma (opcjonalnie)</label>
                        <input id="company" name="company" value="{{ old('company') }}"
                               class="w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-sm text-chrome outline-none focus:border-brand">
                        @error('company') <p class="mt-1 text-xs text-warn">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="message" class="block text-xs text-muted mb-1">Wiadomość</label>
                        <textarea id="message" name="message" rows="5" required
                                  class="w-full rounded-lg border border-ink-line bg-ink px-4 py-2.5 text-sm text-chrome outline-none focus:border-brand">{{ old('message') }}</textarea>
                        @error('message') <p class="mt-1 text-xs text-warn">{{ $message }}</p> @enderror
                    </div>

                    <p class="sm:col-span-2 text-xs leading-relaxed text-muted">
                        Dane z formularza są przetwarzane wyłącznie w celu obsługi zapytania.
                        Szczegóły zawiera <a href="/polityka-prywatnosci" class="text-brand hover:underline">polityka prywatności</a>.
                    </p>

                    <div class="sm:col-span-2 mt-2 flex justify-center border-t border-ink-line pt-6">
                        <button type="submit" class="rounded-md border border-ink-line bg-ink-raised px-6 py-2.5 text-sm font-medium text-chrome hover:border-brand transition-colors">
                            Wyślij zapytanie
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    @include('partials.site-footer')


    @include('partials.cookie-consent')
</body>
</html>