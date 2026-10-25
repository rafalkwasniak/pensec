<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RememberLeadSource;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The public contact form. A lead is stored first so it is never lost, then a
 * notification is emailed best-effort to config('pensec.leads.notify_to'). A
 * mail failure is reported, not surfaced - the enquiry is already saved and
 * shows up in the panel.
 */
class LeadController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Honeypot: a field no real person sees or fills. A bot that fills it is
        // answered with the same success and nothing is stored or sent.
        if (filled($request->input('website'))) {
            return back()->with('lead_status', 'ok');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'company' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $lead = Lead::create(array_merge(
            $data,
            (array) $request->session()->get(RememberLeadSource::SESSION_KEY, []),
            ['source_ip' => $request->ip()],
        ));

        try {
            Mail::raw(
                "Imię: {$lead->name}\nE-mail: {$lead->email}\nFirma: ".($lead->company ?: '—')."\nŹródło: {$lead->sourceLabel()}\n\n{$lead->message}",
                function ($mail) use ($lead): void {
                    $mail->to(config('pensec.leads.notify_to'))
                        ->replyTo($lead->email, $lead->name)
                        ->subject('Nowe zapytanie z pensec.top: '.$lead->name);
                },
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return back()->with('lead_status', 'ok');
    }
}
