{{--
    No facts table: the summary is prose over the whole badanie, and every
    figure it may cite is shown on the cover and in the sections that follow.

    The one standing element is the personal-data note: when the scan reached
    personal data in the network (PESEL, card numbers), its exposure carries a
    RODO/UODO obligation. Written by the template, not the model, and triggered
    by the finding in $facts - it reads the same in both reports and never
    appears when no personal data was found.
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
