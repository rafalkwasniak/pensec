{{--
    Certyfikat renderowany przez dompdf: tylko inline CSS i tabele, bez JS, bez
    WebP. Logo w PNG spłaszczonym do druku, font DejaVu Sans dla polskich znaków.
    Papier ustawiany w kontrolerze: A4 poziomo.
--}}
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; }
        .frame {
            margin: 12mm;
            height: 180mm;
            border: 2px solid #12a5ff;
        }
        .inner { padding: 0 24mm; }
        .label { font-size: 13pt; letter-spacing: 4px; color: #6b7280; text-transform: uppercase; }
        .rule { width: 24mm; height: 1.2mm; background: #12a5ff; margin: 7mm auto; }
        .name { font-size: 30pt; font-weight: bold; text-transform: uppercase; }
        .desc { font-size: 13pt; color: #374151; line-height: 1.7; }
        .foot { width: 100%; margin-top: 14mm; font-size: 10pt; color: #9ca3af; }
        .foot .mid { text-align: center; color: #111827; letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="frame">
        <table style="width: 100%; height: 100%;">
            <tr>
                <td style="vertical-align: middle; text-align: center;">
                    <div class="inner">
                        <img src="{{ public_path('images/pensec-logo-print.png') }}" style="height: 20mm;" alt="Pensec">
                        <div class="label" style="margin-top: 12mm;">Certyfikat ukończenia kursu</div>
                        <div class="rule"></div>
                        <div class="name">{{ $certificate->holder_name }}</div>
                        <div class="rule"></div>
                        <div class="desc">
                            Ukończył z wynikiem pozytywnym szkolenie z oceny ekspozycji<br>
                            infrastruktury wewnętrznej, obejmujące rozpoznanie, enumerację usług<br>
                            oraz analizę baz danych i raportowanie.
                        </div>
                        <table class="foot">
                            <tr>
                                <td style="text-align: left; width: 33%;">{{ $certificate->formattedId() }}</td>
                                <td class="mid" style="width: 34%;">pensec.top</td>
                                <td style="text-align: right; width: 33%;">{{ $certificate->created_at->format('d.m.Y') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
