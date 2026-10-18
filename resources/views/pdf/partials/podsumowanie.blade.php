{{--
    No facts table: the summary is prose over the whole badanie, and every
    figure it may cite is shown on the cover and in the sections that follow.

    Two standing elements, both written by the template rather than the model:

    - the personal-data note: when the scan reached personal data in the network
      (PESEL, card numbers), its exposure carries a RODO/UODO obligation. Triggered
      by the finding in $facts, it never appears when no personal data was found.
    - the regulatory-context note: a neutral framing of how the badanie relates to
      NIS2 art. 21 and ISO/IEC 27000. Unconditional, and it disclaims in as many
      words that it is not a certification or a conformity assessment - the one claim
      about the document's legal standing, so it is fixed text and never generated.

    Both read the same in the expert and the manager report.
--}}
@if ($facts['personal_data'] ?? false)
    <div style="background:#fdf0d5; border-left:3px solid #d99a2b; padding:9px 12px; font-size:10px; color:#5b3d06; margin-bottom:14px;">
        <strong>Dane osobowe w sieci.</strong> Badanie dotarło do danych osobowych dostępnych w
        sieci, takich jak numery PESEL lub dane kart płatniczych. Ich wystawienie bez właściwej
        ochrony podlega obowiązkom wynikającym z RODO oraz ustawy o ochronie danych osobowych.
        Niezależnie od działań technicznych wskazanych w tym raporcie zalecane jest ograniczenie
        dostępu do tych danych oraz ustalenie, czy sytuacja wymaga zgłoszenia do organu nadzorczego.
        Uwaga ta wskazuje wagę ustalenia i nie stanowi porady prawnej.
    </div>
@endif

<div style="background:#eef2f9; border-left:3px solid #8a97ab; padding:9px 12px; font-size:10px; color:#3b475c; margin-bottom:14px;">
    <strong>Kontekst regulacyjny.</strong> Zakres tego badania, obejmujący rozpoznanie wystawionych
    usług, słabości konfiguracji i znanych podatności w sieci wewnętrznej, odpowiada obszarom
    zarządzania ryzykiem i bezpieczeństwa sieci, o których mowa w art. 21 dyrektywy NIS2, oraz dobrym
    praktykom rodziny norm ISO/IEC 27000. Raport wspiera realizację tych obowiązków, dostarczając
    obrazu stanu zabezpieczeń. Nie jest certyfikacją, audytem zgodności ani oceną spełnienia wymagań
    tych regulacji.
</div>
