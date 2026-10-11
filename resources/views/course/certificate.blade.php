<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Certyfikat | Pensec</title>
    @vite('resources/css/app.css')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
        
        body, html {
            background-color: #0b0f19;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden; /* Bezwzględne zablokowanie scrollowania na urządzeniach mobilnych */
        }
        
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Nowa metoda pozycjonowania - absolutny środek niezależnie od urządzenia */
        #cert-scaling-wrapper {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 1123px;
            height: 794px;
            /* Transformacja scale jest aplikowana przez JS do tego translate */
            transform: translate(-50%, -50%) scale(1); 
        }

        .cert-grid {
            background-color: #ffffff;
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 12px 12px;
            background-position: center center;
        }

        /* Reset układu dla drukarki systemu operacyjnego */
        @media print {
            body, html { background: white; margin: 0; padding: 0; width: 100%; height: auto; overflow: visible; display: block; }
            .no-print { display: none !important; }
            #cert-scaling-wrapper { 
                position: relative !important; 
                top: auto !important; 
                left: auto !important; 
                transform: none !important; 
                margin: 0 !important; 
            }
            #cert-container { box-shadow: none !important; }
            @page { size: A4 landscape; margin: 0; }
        }
    </style>
</head>
<body>

    <!-- PASEK AKCJI (nie drukuje się) -->
    <div class="no-print fixed top-4 right-4 z-50 flex gap-2">
        <a href="{{ route('course.certificate.pdf', $certificate) }}" class="rounded-md border border-ink-line bg-ink-raised px-4 py-2 text-sm font-medium text-chrome transition-colors hover:border-brand">
            Pobierz PDF
        </a>
        <a href="/course" class="rounded-md border border-ink-line bg-ink-raised px-4 py-2 text-sm font-medium text-muted transition-colors hover:text-chrome">
            Wróć do kursu
        </a>
    </div>

    <!-- WEWNĘTRZNY KONTENER SKALOWANY (Absolutny środek) -->
    <div id="cert-scaling-wrapper">
        
        <!-- RAMKA GRADIENTOWA -->
        <div id="cert-container" class="w-full h-full shadow-[0_0_80px_rgba(0,0,0,0.8)] p-[12px] bg-gradient-to-b from-brand to-slate-950">
            
            <!-- CERTYFIKAT A4 Z SIATKĄ -->
            <div class="cert-grid w-full h-full flex flex-col justify-between p-16 relative bg-white">

                <!-- GÓRA: Wyśrodkowane logo -->
                <div class="w-full flex justify-center pt-8">
                    <img src="/images/pensec-logo-light.webp" alt="Pensec" class="h-20">
                </div>

                <!-- ŚRODEK: Kaskada tekstu -->
                <div class="flex-grow flex flex-col justify-center items-center text-center px-8 mt-6">
                    
                    <h1 class="text-base font-medium text-gray-500 tracking-[0.3em] uppercase mb-8">
                        Certyfikat ukończenia kursu pentesterskiego
                    </h1>

                    <!-- MINIMALISTYCZNY SEPARATOR (BRAND) -->
                    <div class="w-16 h-1 bg-brand mb-8 opacity-90"></div>

                    <!-- IMIĘ I NAZWISKO (bohater certyfikatu) -->
                    <h2 class="text-5xl leading-[1.1] font-bold text-gray-900 tracking-tight mb-12 uppercase break-words max-w-4xl">
                        {{ $certificate->holder_name }}
                    </h2>
                    
                    <!-- MINIMALISTYCZNY SEPARATOR (BRAND) -->
                    <div class="w-16 h-1 bg-brand mb-10 opacity-90"></div>

                    <!-- OPIS -->
                    <p class="text-xl font-light text-gray-700 leading-[1.8] max-w-5xl">
                        Ukończył z wynikiem pozytywnym autorski program szkoleniowy.<br>
                        Zakres weryfikacji objął zaawansowane techniki ofensywne,<br>
                        przełamywanie segmentacji sieciowej, analizę przestrzeni radiowej<br>
                        oraz eksplorację środowisk usług katalogowych.
                    </p>

                </div>

                <!-- DÓŁ: Symetryczna stopka z głębokimi marginesami (px-36) -->
                <div class="w-full flex justify-between items-center border-t border-gray-200 pt-8 pb-4 px-36 font-mono text-base text-gray-400 tracking-widest">
                    
                    <!-- Lewa strona: ID -->
                    <div class="text-left w-1/3">
                        {{ $certificate->formattedId() }}
                    </div>

                    <!-- Środek: Domena -->
                    <div class="text-center w-1/3 text-gray-900 font-medium tracking-[0.2em] lowercase">
                        pensec.top
                    </div>

                    <!-- Prawa strona: Data -->
                    <div class="text-right w-1/3">
                        {{ $certificate->created_at->format('d.m.Y') }}
                    </div>

                </div>

            </div>
        </div>
        
    </div>

    <!-- SKRYPT SKALUJĄCY DO ŚRODKA EKRANU -->
    <script>
        function resizeCertificate() {
            const scalingWrapper = document.getElementById('cert-scaling-wrapper');
            
            // Pobieramy absolutną przestrzeń całego okna (viewport)
            // Zostawiamy 16px marginesu ochronnego
            const availableWidth = window.innerWidth - 32; 
            const availableHeight = window.innerHeight - 32;
            
            // Obliczamy skale dla obu osi. Bierzemy mniejszą, by dokument na pewno się zmieścił.
            const scaleX = availableWidth / 1123;
            const scaleY = availableHeight / 794;
            const scale = Math.min(scaleX, scaleY, 1);
            
            // Klucz: dodajemy translate(-50%, -50%), aby utrzymać centrowanie absolutne przy skalowaniu
            scalingWrapper.style.transform = `translate(-50%, -50%) scale(${scale})`;
        }

        window.addEventListener('resize', resizeCertificate);
        
        document.addEventListener('DOMContentLoaded', () => {
            resizeCertificate();
        });
    </script>
</body>
</html>