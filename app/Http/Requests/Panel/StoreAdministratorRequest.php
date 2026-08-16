<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreAdministratorRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('panel.validation.account_name_required'),
            'email.required' => __('panel.validation.email_required'),
            'email.email' => __('panel.validation.email_invalid'),
            'email.unique' => __('panel.validation.email_taken'),
            'password.required' => __('panel.validation.password_required'),
            'password.confirmed' => __('panel.validation.password_not_confirmed'),
            'password.min' => __('panel.validation.password_too_short'),
        ];
    }
}
