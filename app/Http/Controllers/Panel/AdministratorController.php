<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\StoreAdministratorRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdministratorController extends Controller
{
    public function index(): View
    {
        return view('panel.administrators.index', [
            'administrators' => User::orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('panel.administrators.create');
    }

    public function store(StoreAdministratorRequest $request): RedirectResponse
    {
        // The panel is closed, so an account created here is usable at once:
        // there is nobody outside to confirm an address to.
        $administrator = User::create($request->validated());

        return redirect()
            ->route('panel.administrators.index')
            ->with('status', __('panel.administrators.created', ['name' => $administrator->name]));
    }

    public function destroy(Request $request, User $administrator): RedirectResponse
    {
        if ($administrator->is($request->user())) {
            return redirect()
                ->route('panel.administrators.index')
                ->with('error', __('panel.administrators.cannot_remove_self'));
        }

        $name = $administrator->name;
        $administrator->delete();

        return redirect()
            ->route('panel.administrators.index')
            ->with('status', __('panel.administrators.removed', ['name' => $name]));
    }
}
