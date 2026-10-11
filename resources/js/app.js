// ==========================================
// Akademia Pensec (kurs) - logika strony lekcji
// Ładowane przez Vite w resources/views/course/reader.blade.php.
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobilne menu (panel boczny). Widoczność sterowana klasą
    //    .sidebar-open zdefiniowaną w <style> readera.
    const btnOpen = document.getElementById('mobile-menu-btn');
    const btnClose = document.getElementById('close-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');

    function toggleMenu() {
        if (!sidebar || !overlay) return;

        if (sidebar.classList.contains('sidebar-open')) {
            sidebar.classList.remove('sidebar-open');
            overlay.classList.add('opacity-0');
            document.body.classList.remove('overflow-hidden');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        } else {
            sidebar.classList.add('sidebar-open');
            overlay.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => overlay.classList.remove('opacity-0'), 10);
        }
    }

    if (btnOpen) btnOpen.addEventListener('click', toggleMenu);
    if (btnClose) btnClose.addEventListener('click', toggleMenu);
    if (overlay) overlay.addEventListener('click', toggleMenu);

    // 2. Pasek postępu czytania.
    const progressBar = document.getElementById('scroll-progress');
    if (progressBar) {
        window.addEventListener('scroll', () => {
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrollPercentage = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
            progressBar.style.width = scrollPercentage + '%';
        });
    }

    // 3. Fragmenty w terminalu: każdy blok kodu dostaje pasek z etykietą języka
    //    (z klasy language-* od Markdowna) i przycisk Kopiuj. Style: .pensec-terminal*
    //    w app.css. Bez JS blok i tak renderuje się jako czytelny panel (<pre>).
    const langLabels = { bash: 'bash', sh: 'bash', shell: 'bash', text: 'wynik', console: 'konsola' };

    document.querySelectorAll('.pensec-markdown pre').forEach((pre) => {
        const code = pre.querySelector('code');
        const match = code && code.className.match(/language-([\w-]+)/);
        const lang = match ? match[1] : '';
        const label = langLabels[lang] || lang || 'kod';

        const wrap = document.createElement('div');
        wrap.className = 'pensec-terminal';
        pre.parentNode.insertBefore(wrap, pre);

        const bar = document.createElement('div');
        bar.className = 'pensec-terminal-bar';

        const tag = document.createElement('span');
        tag.className = 'pensec-terminal-lang';
        tag.textContent = label;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pensec-terminal-copy';
        btn.textContent = 'Kopiuj';
        btn.addEventListener('click', () => {
            navigator.clipboard.writeText(code ? code.innerText : pre.innerText).then(() => {
                btn.textContent = 'Skopiowano';
                btn.classList.add('is-copied');
                setTimeout(() => {
                    btn.textContent = 'Kopiuj';
                    btn.classList.remove('is-copied');
                }, 2000);
            });
        });

        bar.appendChild(tag);
        bar.appendChild(btn);
        wrap.appendChild(bar);
        wrap.appendChild(pre); // przenosi <pre> do wrappera - staje się ciałem terminala
    });

    // 4. Odhaczanie przeczytanych lekcji w menu (pamięć przeglądarki).
    const currentSlug = document.body.getAttribute('data-course-slug');
    if (currentSlug) {
        const storageKey = 'pensec_course_progress';
        let progress = [];

        try {
            const stored = JSON.parse(localStorage.getItem(storageKey));
            if (Array.isArray(stored)) {
                progress = stored;
            }
        } catch (e) {
            // Uszkodzona lub niedostępna pamięć - startujemy z pustym postępem.
        }

        if (!progress.includes(currentSlug)) {
            progress.push(currentSlug);
            try {
                localStorage.setItem(storageKey, JSON.stringify(progress));
            } catch (e) {
                // Zapis niemożliwy (np. tryb prywatny) - pomijamy.
            }
        }

        const checkIcon = '<svg class="w-4 h-4 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
        const circleIcon = '<svg class="w-2 h-2 text-ink-line rounded-full fill-current" viewBox="0 0 24 24"><circle cx="12" cy="12" r="12"/></svg>';

        document.querySelectorAll('.course-link').forEach((link) => {
            const linkSlug = link.getAttribute('data-slug');
            const iconContainer = link.querySelector('.status-icon');
            if (iconContainer) {
                iconContainer.innerHTML = progress.includes(linkSlug) ? checkIcon : circleIcon;
            }
        });
    }

    // 5. Checkpoint na końcu rozdziału. Odpowiedzi ocenia serwer; strona pokazuje,
    //    które były trafne, i wynik procentowy. Poprawne indeksy nie są w stronie.
    const checkpoint = document.getElementById('checkpoint');
    if (checkpoint) {
        const submitBtn = document.getElementById('checkpoint-submit');
        const scoreEl = document.getElementById('checkpoint-score');
        const url = checkpoint.getAttribute('data-url');
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const groups = Array.from(checkpoint.querySelectorAll('.checkpoint-q'));

        submitBtn.addEventListener('click', async () => {
            const answers = [];
            for (const group of groups) {
                const idx = group.getAttribute('data-index');
                const chosen = checkpoint.querySelector('input[name="cq' + idx + '"]:checked');
                if (!chosen) {
                    scoreEl.textContent = 'Zaznacz odpowiedź w każdym pytaniu.';
                    return;
                }
                answers.push(parseInt(chosen.value, 10));
            }

            submitBtn.disabled = true;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': tokenMeta ? tokenMeta.getAttribute('content') : '',
                    },
                    body: JSON.stringify({ answers }),
                });
                const data = await response.json();
                groups.forEach((group, i) => {
                    const idx = group.getAttribute('data-index');
                    const label = checkpoint.querySelector('input[name="cq' + idx + '"]:checked').closest('label');
                    label.classList.remove('border-ink-line');
                    label.classList.add(data.results[i] ? 'border-brand' : 'border-warn');
                });
                scoreEl.textContent = 'Wynik: ' + data.score + '% (' + data.correct + '/' + data.total + ')';
            } catch (e) {
                scoreEl.textContent = 'Nie udało się ocenić. Spróbuj ponownie.';
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
});
