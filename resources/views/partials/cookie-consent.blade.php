{{-- Panel akceptacji cookie. Samodzielny: własny skrypt, zgoda w localStorage.
     Spójny z nawigacją (bg-ink-raised, backdrop-blur, tokeny, oba motywy). --}}
<div id="cookie-consent" class="fixed inset-x-0 bottom-0 z-50 hidden px-4 pb-4 sm:px-6">
    <div class="mx-auto flex max-w-3xl flex-col gap-4 rounded-xl border border-ink-line bg-ink-raised/90 px-5 py-4 shadow-lg backdrop-blur-md sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs leading-relaxed text-muted sm:text-sm">
            Serwis używa wyłącznie niezbędnych plików cookie, koniecznych do działania strony i utrzymania sesji. Nie stosujemy śledzenia ani profilowania.
        </p>
        <button id="cookie-accept" type="button" class="shrink-0 whitespace-nowrap rounded-md border border-ink-line bg-ink-raised px-5 py-2.5 text-sm font-medium text-chrome transition-colors hover:border-brand">
            Akceptuję
        </button>
    </div>
</div>
<script>
    (function () {
        var KEY = 'pensec.cookie-consent';
        var box = document.getElementById('cookie-consent');
        if (!box) return;

        var accepted = false;
        try { accepted = localStorage.getItem(KEY) === '1'; } catch (e) {}
        if (!accepted) box.classList.remove('hidden');

        var btn = document.getElementById('cookie-accept');
        if (btn) {
            btn.addEventListener('click', function () {
                try { localStorage.setItem(KEY, '1'); } catch (e) {}
                box.classList.add('hidden');
            });
        }
    })();
</script>
