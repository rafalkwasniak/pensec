<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(): View
    {
        return view('panel.leads.index', [
            'leads' => Lead::latest()->paginate(25),
        ]);
    }

    /** Toggles whether an enquiry has been dealt with. */
    public function handled(Lead $lead): RedirectResponse
    {
        $lead->forceFill(['handled_at' => $lead->handled_at === null ? now() : null])->save();

        return back();
    }
}
