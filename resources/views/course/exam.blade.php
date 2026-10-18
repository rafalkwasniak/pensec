<!DOCTYPE html>
<html lang="pl" class="scroll-smooth" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | Pensec</title>
    @include('partials.theme-boot')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen flex flex-col antialiased bg-ink">

    <!-- NAGŁÓWEK -->
    <header class="w-full flex items-center justify-between px-6 lg:px-8 py-4 border-b border-ink-line bg-ink">
        <a href="/course/start" class="flex items-center hover:opacity-80 transition-opacity">
            <img src="/images/pensec-logo.webp" alt="Pensec" class="theme-when-dark h-5 w-auto">
            <img src="/images/pensec-logo-light.webp" alt="Pensec" class="theme-when-light h-5 w-auto">
        </a>
        <div class="flex items-center">
            @include('partials.theme-toggle')
        </div>
    </header>

    <!-- KONTENER GŁÓWNY -->
    <main class="flex-grow w-full mx-auto max-w-4xl px-4 sm:px-6 py-10 lg:py-16">
        
        <!-- KAFELEK GŁÓWNY -->
        <div class="card p-6 sm:p-10 lg:p-12 min-h-[500px] flex flex-col">

            <!-- NAGŁÓWEK SPÓJNY Z KURSAMI -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <span class="font-mono text-sm text-brand tracking-widest uppercase">Zakończenie</span>
                    <span id="question-counter" class="font-mono text-sm text-muted">1 / 12</span>
                </div>
                <h1 class="text-3xl font-semibold chrome-text">{{ $title }}</h1>
            </div>

            <!-- PASEK POSTĘPU -->
            <div id="progress-container" class="w-full bg-ink-line h-[2px] mb-12">
                <div id="progress-bar" class="bg-brand h-[2px] transition-all duration-300" style="width: 0%"></div>
            </div>

            <!-- EKRAN TESTU -->
            <div id="exam-screen" class="flex flex-col flex-grow hidden">
                
                <!-- Kontener Kroku -->
                <div class="flex-grow">
                    <span id="step-type" class="hidden mb-3 inline-block font-mono text-xs uppercase tracking-widest text-brand"></span>
                    <pre id="step-context" class="hidden mb-6 overflow-x-auto rounded-lg border border-ink-line bg-ink p-4 font-mono text-sm text-chrome"></pre>
                    <h2 id="question-text" class="text-xl sm:text-2xl font-medium chrome-text mb-10 leading-relaxed">
                        <!-- Treść generowana przez JS -->
                    </h2>

                    <!-- Odpowiedzi -->
                    <div id="options-container" class="space-y-5">
                        <!-- Odpowiedzi generowane przez JS -->
                    </div>
                </div>

                <!-- Nawigacja Pytania -->
                <div class="flex justify-start mt-12 pt-8 border-t border-ink-line">
                    <button id="next-btn" onclick="nextStep()" disabled class="rounded-md border border-ink-line bg-ink px-6 py-3 text-sm font-medium text-chrome opacity-50 cursor-not-allowed transition-all">
                        Potwierdź odpowiedź
                    </button>
                </div>
            </div>

            <!-- EKRAN WYNIKÓW -->
            <div id="results-screen" class="hidden flex-col flex-grow justify-center text-center">
                <div class="text-6xl font-mono text-brand mb-10 mt-4" id="score-display">0%</div>
                
                <p id="success-msg" class="hidden text-base text-muted leading-relaxed mb-8 mx-auto max-w-2xl">
                    Gratulacje. Wymagany próg 75% został osiągnięty. Wprowadź imię i nazwisko aby odebrać certyfikat ukończenia kursu.
                </p>
                <p id="fail-msg" class="hidden text-base text-muted leading-relaxed mb-8 mx-auto max-w-2xl">
                    Wynik testu nie osiągnął wymaganego progu 75%. Wskazana jest ponowna analiza modułów szkoleniowych przed kolejnym podejściem. Test można rozwiązywać wielokrotnie.
                </p>

                <!-- FORMULARZ CERTYFIKATU (Widoczny po zdaniu) -->
                <div id="cert-form" class="hidden mt-8 pt-8 border-t border-ink-line mx-auto w-full max-w-md">
                    <!-- Ujednolicone input field w stylu panelu -->
                    <input type="text" id="student-name" placeholder="Imię i nazwisko" class="w-full bg-ink-raised border border-ink-line rounded-md px-4 py-3 text-chrome font-medium focus:outline-none focus:border-brand transition-colors mb-4 placeholder-muted text-center" autocomplete="off">
                    
                    <!-- Ujednolicony przycisk -->
                    <button id="cert-btn" onclick="generateCertificate()" class="w-full rounded-md border border-ink-line bg-ink-raised px-6 py-3 text-sm font-medium text-chrome hover:border-brand transition-colors">
                        Odbierz certyfikat
                    </button>
                </div>

                <!-- PRZYCISK POWTÓRKI (Widoczny po oblaniu) -->
                <div id="retry-container" class="hidden mt-8 pt-8 border-t border-ink-line flex justify-center">
                    <button onclick="location.reload()" class="rounded-md border border-ink-line bg-ink-raised px-6 py-3 text-sm font-medium text-chrome hover:border-brand transition-colors">
                        Ponów test
                    </button>
                </div>
            </div>

        </div>

    </main>

    <!-- STOPKA -->
    <footer class="border-t border-ink-line mt-auto bg-ink">
        <div class="mx-auto flex max-w-6xl items-center justify-center px-6 py-10 text-sm text-muted">
            <div class="flex items-center gap-1.5">
                <img src="/images/pensec-logo.webp" alt="Pensec" width="768" height="256" class="theme-when-dark h-4 sm:h-5 w-auto">
                <img src="/images/pensec-logo-light.webp" alt="Pensec" width="768" height="256" class="theme-when-light h-4 sm:h-5 w-auto">
                <span class="font-semibold tracking-wide text-chrome">&copy;</span>
                <span class="font-semibold tracking-wide text-chrome">{{ date('Y') }}</span>
            </div>
        </div>
    </footer>

    <script>
        // Kroki bez pola 'correct' - wstrzyknięte przez serwer. Odpowiedzi
        // ocenia backend, więc nie da się ich odczytać ze źródła strony.
        const steps = @json($steps);
        const PASS = {{ $passThreshold }};
        const SUBMIT_URL = @json(route('course.exam.submit'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const typeLabels = { komenda: 'Wskaż komendę', interpretacja: 'Interpretacja wyniku', decyzja: 'Następny krok', finding: 'Ocena ustalenia' };

        let currentStepIndex = 0;
        let selectedOption = null;
        let finished = false;
        const answers = new Array(steps.length).fill(null);

        const examScreen = document.getElementById('exam-screen');
        const resultsScreen = document.getElementById('results-screen');
        const stepType = document.getElementById('step-type');
        const stepContext = document.getElementById('step-context');
        const questionText = document.getElementById('question-text');
        const optionsContainer = document.getElementById('options-container');
        const nextBtn = document.getElementById('next-btn');
        const progressBar = document.getElementById('progress-bar');
        const questionCounter = document.getElementById('question-counter');

        document.addEventListener('DOMContentLoaded', () => {
            examScreen.classList.remove('hidden');
            loadStep();
        });

        function loadStep() {
            selectedOption = null;
            nextBtn.disabled = true;
            nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
            nextBtn.classList.remove('hover:border-brand', 'cursor-pointer', 'bg-ink-raised');
            nextBtn.classList.add('bg-ink');
            nextBtn.innerText = currentStepIndex === steps.length - 1 ? 'Zakończ egzamin' : 'Potwierdź odpowiedź';

            const step = steps[currentStepIndex];

            if (step.type && typeLabels[step.type]) {
                stepType.innerText = typeLabels[step.type];
                stepType.classList.remove('hidden');
            } else {
                stepType.classList.add('hidden');
            }

            if (step.context) {
                stepContext.innerText = step.context;
                stepContext.classList.remove('hidden');
            } else {
                stepContext.classList.add('hidden');
            }

            questionText.innerText = step.prompt;
            questionCounter.innerText = `${currentStepIndex + 1} / ${steps.length}`;

            const progressPercentage = (currentStepIndex / steps.length) * 100;
            progressBar.style.width = `${progressPercentage}%`;

            optionsContainer.innerHTML = '';

            step.options.forEach((opt, index) => {
                const optDiv = document.createElement('div');
                optDiv.className = 'p-5 rounded-md border border-ink-line bg-ink cursor-pointer hover:bg-ink-raised transition-colors text-muted text-base leading-relaxed';
                optDiv.innerText = opt;
                optDiv.onclick = () => selectOption(index, optDiv);
                optionsContainer.appendChild(optDiv);
            });
        }

        function selectOption(index, element) {
            selectedOption = index;

            const allOptions = optionsContainer.children;
            for (let i = 0; i < allOptions.length; i++) {
                allOptions[i].classList.remove('border-brand', 'bg-ink-raised', 'text-chrome');
                allOptions[i].classList.add('border-ink-line', 'bg-ink', 'text-muted');
            }

            element.classList.remove('border-ink-line', 'bg-ink', 'text-muted');
            element.classList.add('border-brand', 'bg-ink-raised', 'text-chrome');

            nextBtn.disabled = false;
            nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            nextBtn.classList.add('hover:border-brand', 'cursor-pointer', 'bg-ink-raised');
            nextBtn.classList.remove('bg-ink');
        }

        function nextStep() {
            if (selectedOption === null || finished) return;

            answers[currentStepIndex] = selectedOption;
            currentStepIndex++;

            if (currentStepIndex < steps.length) {
                loadStep();
            } else {
                finishExam();
            }
        }

        // Jeden kanał do serwera: wysyła odpowiedzi (i opcjonalnie imię).
        // Serwer zawsze ocenia u siebie - przy podanym imieniu i zdanym teście
        // zakłada certyfikat i zwraca jego adres.
        async function postExam(payload) {
            const response = await fetch(SUBMIT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error('Serwer odrzucił zgłoszenie (' + response.status + ').');
            }

            return response.json();
        }

        async function finishExam() {
            finished = true;
            questionCounter.innerText = 'Ocenianie…';
            progressBar.style.width = '100%';

            try {
                const result = await postExam({ answers });
                showResults(result);
            } catch (e) {
                // Pozwól spróbować jeszcze raz zamiast zostawiać pusty ekran.
                finished = false;
                questionCounter.innerText = 'Błąd';
                alert('Nie udało się ocenić testu. Sprawdź połączenie i spróbuj ponownie.');
                nextBtn.disabled = false;
                nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        function showResults(result) {
            examScreen.classList.remove('flex');
            examScreen.classList.add('hidden');
            resultsScreen.classList.remove('hidden');
            resultsScreen.classList.add('flex');

            questionCounter.innerText = 'Zakończono';
            document.getElementById('score-display').innerText = `${result.score}%`;

            if (result.passed) {
                document.getElementById('success-msg').innerText =
                    `Gratulacje. Wymagany próg ${PASS}% został osiągnięty. Wprowadź imię i nazwisko, aby odebrać certyfikat ukończenia kursu.`;
                document.getElementById('success-msg').classList.remove('hidden');
                document.getElementById('cert-form').classList.remove('hidden');
            } else {
                document.getElementById('fail-msg').classList.remove('hidden');
                document.getElementById('retry-container').classList.remove('hidden');
            }
        }

        async function generateCertificate() {
            const inputField = document.getElementById('student-name');
            const button = document.getElementById('cert-btn');
            const name = inputField.value.trim();

            if (!name) {
                inputField.classList.add('border-red-500');
                inputField.classList.remove('border-ink-line');
                return;
            }

            inputField.classList.remove('border-red-500');
            inputField.classList.add('border-ink-line');

            button.disabled = true;
            button.innerText = 'Generowanie…';

            try {
                const result = await postExam({ answers, name });
                if (result.certificate_url) {
                    window.location.href = result.certificate_url;
                    return;
                } else {
                    button.innerText = 'Odbierz certyfikat';
                    alert('Nie udało się wydać certyfikatu.');
                }
            } catch (e) {
                button.innerText = 'Odbierz certyfikat';
                alert('Nie udało się wydać certyfikatu. Spróbuj ponownie.');
            } finally {
                button.disabled = false;
            }
        }
    </script>

    @include('partials.cookie-consent')
</body>
</html>