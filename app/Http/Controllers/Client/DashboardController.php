<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $client = $request->user('client');

        $reports = Report::whereHas('device', fn ($query) => $query->where('client_id', $client->id))
            ->with('device')
            ->latest('received_at')
            ->paginate(25);

        return view('client.dashboard', ['reports' => $reports, 'client' => $client]);
    }
}
