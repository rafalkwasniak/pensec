{{-- Karta Open Graph (podgląd linku w komunikatorach i serwisach społecznościowych).
     Obraz w PNG, bo część serwisów nie renderuje podglądu z WebP. --}}
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ url('/images/pensec-logo-print.png') }}">
<meta property="og:type" content="{{ $type ?? 'website' }}">
<meta property="og:locale" content="pl_PL">
<meta property="og:site_name" content="Pensec">
