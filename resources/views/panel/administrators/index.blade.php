@extends('panel.layout')

@section('title', 'Administratorzy')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold chrome-text">Administratorzy</h1>
            <p class="mt-2 text-sm text-muted">Konta z dostępem do tego panelu.</p>
        </div>

        <a href="{{ route('panel.administrators.create') }}"
           class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-brand/90">
            Dodaj administratora
        </a>
    </div>

    <div class="card mt-8 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-line text-xs uppercase tracking-widest text-muted">
                <tr>
                    <th class="px-5 py-3 font-medium">Imię i nazwisko</th>
                    <th class="px-5 py-3 font-medium">Adres e-mail</th>
                    <th class="px-5 py-3 font-medium">Dodano</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($administrators as $administrator)
                    <tr class="border-b border-ink-line/60 last:border-0">
                        <td class="px-5 py-4 font-medium text-chrome">
                            {{ $administrator->name }}
                            @if ($administrator->is(auth()->user()))
                                <span class="ml-2 rounded-full border border-brand/40 bg-brand/10 px-3 py-1 text-xs text-brand">to Ty</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-muted">{{ $administrator->email }}</td>
                        <td class="px-5 py-4 text-muted">{{ $administrator->created_at->format('Y-m-d') }}</td>
                        <td class="px-5 py-4 text-right">
                            @unless ($administrator->is(auth()->user()))
                                <form method="POST" action="{{ route('panel.administrators.destroy', $administrator) }}"
                                      onsubmit="return confirm('Usunąć konto {{ $administrator->name }}? Straci dostęp do panelu.')">
                                    @csrf
                                    <button type="submit" class="text-muted transition hover:text-warn">Usuń</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $administrators->links() }}</div>

    <p class="mt-6 max-w-3xl text-sm leading-relaxed text-muted">
        Panel jest zamknięty, więc nowe konto działa od razu po utworzeniu - nie ma żadnej aktywacji ani
        wiadomości e-mail. Hasło ustawiasz przy dodawaniu i przekazujesz je tej osobie, a ona może je
        zmienić w zakładce Konto.
    </p>
@endsection
