<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Polityka prywatności | Pensec</title>
    <meta name="description" content="Zasady przetwarzania danych osobowych osób odwiedzających stronę pensec.top, korzystających z formularza kontaktowego i z portalu klienta.">
    @include('partials.og', ['title' => 'Polityka prywatności | Pensec', 'description' => 'Zasady przetwarzania danych osobowych osób odwiedzających stronę pensec.top, korzystających z formularza kontaktowego i z portalu klienta.'])
    <link rel="canonical" href="{{ url('/polityka-prywatnosci') }}">
    <link rel="icon" type="image/png" href="/favicon.png">
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- KONTENER NAWIGACJI -->
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
        <h1 class="text-3xl font-semibold chrome-text">Polityka prywatności</h1>
        <p class="mt-3 text-sm text-muted">Dotyczy osób odwiedzających stronę pensec.top, korzystających z formularza kontaktowego oraz z portalu klienta.</p>

        <div class="mt-10 space-y-8 text-sm leading-relaxed text-muted">
            <section>
                <h2 class="text-base font-semibold text-chrome">Administrator danych</h2>
                <p class="mt-3">
                    Administratorem danych osobowych jest <strong>[nazwa i adres administratora do uzupełnienia]</strong>.
                    W sprawach dotyczących danych osobowych można kontaktować się pod adresem
                    <a href="mailto:norbert@kwasniak.org" class="text-brand hover:underline">norbert@kwasniak.org</a>.
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-chrome">Zakres i cel przetwarzania danych</h2>
                <ul class="mt-3 space-y-3">
                    <li><strong class="text-chrome">Formularz kontaktowy.</strong> Imię i nazwisko, adres e-mail, nazwa firmy (opcjonalnie) oraz treść wiadomości, a także informacja o źródle wizyty: oznaczenie kampanii z linku (parametry utm), adres strony odsyłającej i strona, od której wizyta się rozpoczęła. Źródło wizyty jest przechowywane w sesji i zapisywane wyłącznie razem z wysłanym zapytaniem. Dane są przetwarzane w celu udzielenia odpowiedzi na zapytanie i prowadzenia ewentualnej współpracy. Podstawą jest prawnie uzasadniony interes w obsłudze korespondencji handlowej oraz podjęcie działań na żądanie osoby przed zawarciem umowy. Dane są przechowywane do czasu zakończenia korespondencji lub współpracy, a następnie przez okres wymagany przepisami.</li>
                    @if (config('pensec.analytics.ga_id'))
                        <li><strong class="text-chrome">Pliki cookie.</strong> Niezbędne do działania strony i utrzymania sesji oraz, wyłącznie po wyrażeniu zgody w banerze, pliki cookie Google Analytics 4 służące do statystyk odwiedzin (liczba wizyt, odwiedzane strony, źródła ruchu, wysłanie formularza). Podstawą jest zgoda, którą można w każdej chwili wycofać przyciskiem „Ustawienia cookie” w stopce strony. Dostawcą usługi jest Google Ireland Limited; dane mogą być przekazywane do Stanów Zjednoczonych na podstawie EU-US Data Privacy Framework. Bez zgody skrypt analityczny nie jest ładowany.</li>
                    @else
                        <li><strong class="text-chrome">Pliki cookie.</strong> Wyłącznie niezbędne do działania strony i utrzymania sesji. Strona nie stosuje śledzenia, profilowania ani analityki marketingowej.</li>
                    @endif
                    <li><strong class="text-chrome">Logi serwera.</strong> Standardowe dane techniczne (adres IP, czas żądania), przetwarzane w celu zapewnienia bezpieczeństwa i prawidłowego działania usługi.</li>
                    <li><strong class="text-chrome">Konto w portalu klienta.</strong> W przypadku klientów z dostępem do portalu przetwarzane są nazwa, adres e-mail oraz hasło, przechowywane wyłącznie w postaci skrótu kryptograficznego, w celu umożliwienia logowania i wglądu w raporty z przypisanych urządzeń. Podstawą jest wykonanie umowy.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-base font-semibold text-chrome">Odbiorcy danych</h2>
                <p class="mt-3">Dane nie są sprzedawane ani udostępniane w celach marketingowych. Mogą zostać powierzone podmiotom świadczącym usługi hostingu i poczty elektronicznej, wyłącznie w zakresie niezbędnym do działania strony{{ config('pensec.analytics.ga_id') ? ', a po wyrażeniu zgody na statystyki także dostawcy Google Analytics' : '' }}.</p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-chrome">Prawa osób, których dane dotyczą</h2>
                <p class="mt-3">Osobie, której dane dotyczą, przysługuje prawo dostępu do danych, ich sprostowania, usunięcia lub ograniczenia przetwarzania, prawo sprzeciwu oraz prawo do przenoszenia danych, a także prawo wniesienia skargi do Prezesa Urzędu Ochrony Danych Osobowych. Żądania dotyczące realizacji tych praw przyjmowane są pod adresem podanym wyżej.</p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-chrome">Dane zebrane podczas audytu</h2>
                <p class="mt-3">Dane pozyskiwane w trakcie badania bezpieczeństwa sieci klienta są przetwarzane na zasadach ustalonych w odrębnej umowie oraz klauzuli poufności (NDA) zawieranej przed rozpoczęciem prac i nie są przedmiotem niniejszej polityki.</p>
            </section>
        </div>

        <p class="mt-10 text-xs text-muted">Ostatnia aktualizacja: 25.10.2026.</p>

        <div class="mt-8">
            <a href="/contact" class="text-sm text-brand hover:underline">&larr; Formularz kontaktowy</a>
        </div>
    </main>

    @include('partials.site-footer')

    @include('partials.cookie-consent')
</body>
</html>
