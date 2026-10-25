@php
    use App\Enums\NarrativeVariant;
    use App\Enums\ReportStatus;

    $ready = collect(NarrativeVariant::cases())
        ->filter(fn (NarrativeVariant $variant): bool => $report->narrative($variant)->isReady());
@endphp

@extends('client.layout')

@section('title', 'Raport badania')

@section('content')
    <a href="{{ route('client.dashboard') }}" class="text-sm text-muted hover:text-chrome">&larr; Moje raporty</a>
    <h1 class="mt-4 text-2xl font-semibold chrome-text">Raport badania</h1>

    <dl class="mt-6 grid gap-6 sm:grid-cols-3">
        @foreach ([
            ['Urządzenie', $report->device->name],
            ['Badanie', $report->scanned_at !== null ? $report->scanned_at->format('Y-m-d H:i').' UTC' : 'nie podano'],
            ['Odebrano', $report->received_at->format('Y-m-d H:i').' UTC'],
        ] as [$label, $value])
            <div class="card p-5">
                <dt class="text-xs uppercase tracking-widest text-muted">{{ $label }}</dt>
                <dd class="mt-2 text-chrome">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <section class="card mt-8 p-6">
        <h2 class="text-base font-semibold text-chrome">Dokumenty do pobrania</h2>
        @if ($ready->isEmpty())
            <p class="mt-2 text-sm text-muted">Dokumenty PDF są w przygotowaniu i pojawią się tutaj po ukończeniu.</p>
        @else
            <div class="mt-4 flex flex-wrap gap-3">
                @foreach ($ready as $variant)
                    <a href="{{ route('client.reports.pdf', [$report, $variant->value]) }}"
                       class="rounded-lg border border-ink-line px-4 py-2 text-sm text-chrome transition hover:border-brand">
                        {{ $variant->label() }}
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @if ($report->status === ReportStatus::Parsed)
        <section class="card mt-8 p-6">
            <h2 class="text-base font-semibold text-chrome">Analiza badania</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                Streszczenie i ustalenia z tego badania, z wyszukiwaniem i filtrowaniem według wagi oraz źródła.
            </p>
            @include('partials.report-analysis', ['filterAction' => 'client.reports.show'])
        </section>
    @else
        <section class="card mt-8 p-6">
            <p class="text-sm text-muted">Analiza tego badania jest w przygotowaniu.</p>
        </section>
    @endif
@endsection
