<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $password = $request->string('password')->toString();

        $request->user()->update(['password' => $password]);

        // A password is usually changed because the old one is no longer
        // trusted, so every other session signed in with it is dropped.
        Auth::logoutOtherDevices($password);

        return redirect()
            ->route('panel.account.edit')
            ->with('status', __('panel.account.password_updated'));
    }
}
