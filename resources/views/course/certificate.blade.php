<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certyfikat | Pensec</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        html, body { margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            background: #0b0f19;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        /* Action bar, never printed. */
        .actions { position: fixed; top: 16px; right: 16px; z-index: 50; display: flex; gap: 8px; }
        .actions a {
            font-size: 14px; text-decoration: none; border-radius: 8px; padding: 8px 16px;
            border: 1px solid rgba(255, 255, 255, 0.12); background: #141a28; color: #e7ecf5;
        }
        .actions a.secondary { color: #8a97ab; }

        /*
            A4 landscape, always proportional. The box fits inside both axes of the
            viewport and its height follows from aspect-ratio, so the A4 proportion
            holds on every screen - phone included - with no JavaScript. Everything
            inside is sized in cqw (1% of this box's width), so the whole layout
            scales as one with the box.
        */
        .cert-fit {
            container-type: inline-size;
            width: min(94vw, calc(90vh * 1123 / 794));
            width: min(94vw, calc(90dvh * 1123 / 794));
            aspect-ratio: 1123 / 794;
        }

        .cert-frame {
            width: 100%; height: 100%;
            padding: 1.1cqw;
            border-radius: 2px;
            background: linear-gradient(to bottom, #12a5ff, #020617);
            box-shadow: 0 0 80px rgba(0, 0, 0, 0.8);
        }

        .cert-grid {
            width: 100%; height: 100%;
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 5.5cqw 6cqw;
            text-align: center;
            background-color: #fff;
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 1.1cqw 1.1cqw;
            background-position: center center;
        }

        .cert-logo { display: block; height: 6.5cqw; margin: 0 auto; }

        .cert-body { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .cert-title { margin: 0; font-size: 1.5cqw; font-weight: 500; color: #6b7280; letter-spacing: 0.3em; text-transform: uppercase; }
        .cert-rule { width: 5.5cqw; height: 0.4cqw; margin: 3cqw auto; background: #12a5ff; opacity: 0.9; }
        .cert-name { margin: 0; font-size: 4.6cqw; line-height: 1.1; font-weight: 700; color: #111827; letter-spacing: -0.01em; text-transform: uppercase; word-break: break-word; }
        .cert-desc { margin: 0; max-width: 80cqw; font-size: 1.75cqw; font-weight: 300; line-height: 1.8; color: #374151; }

        .cert-foot {
            display: flex; justify-content: space-between; align-items: center;
            border-top: 1px solid #e5e7eb; padding-top: 2.5cqw;
            font-family: 'JetBrains Mono', monospace; font-size: 1.3cqw; letter-spacing: 0.15em; color: #9ca3af;
        }
        .cert-foot .mid { color: #111827; letter-spacing: 0.2em; text-transform: lowercase; }

        @media print {
            @page { size: A4 landscape; margin: 0; }
            body { display: block; background: #fff; padding: 0; }
            .actions { display: none; }
            .cert-fit { width: 100%; height: 100vh; }
            .cert-frame { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <a href="{{ route('course.certificate.pdf', $certificate) }}">Pobierz PDF</a>
        <a class="secondary" href="/course">Wróć do kursu</a>
    </div>

    <div class="cert-fit">
        <div class="cert-frame">
            <div class="cert-grid">
                <img class="cert-logo" src="/images/pensec-logo-light.webp" alt="Pensec">

                <div class="cert-body">
                    <p class="cert-title">Certyfikat ukończenia kursu</p>
                    <div class="cert-rule"></div>
                    <h1 class="cert-name">{{ $certificate->holder_name }}</h1>
                    <div class="cert-rule"></div>
                    <p class="cert-desc">
                        Ukończył z wynikiem pozytywnym szkolenie z oceny ekspozycji infrastruktury
                        wewnętrznej, obejmujące rozpoznanie, enumerację usług oraz analizę baz danych
                        i raportowanie.
                    </p>
                </div>

                <div class="cert-foot">
                    <span>{{ $certificate->formattedId() }}</span>
                    <span class="mid">pensec.top</span>
                    <span>{{ $certificate->created_at->format('d.m.Y') }}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
