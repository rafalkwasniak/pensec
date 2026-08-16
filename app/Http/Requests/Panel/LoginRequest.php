<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('panel.validation.email_required'),
            'email.email' => __('panel.validation.email_invalid'),
            'password.required' => __('panel.validation.password_required'),
        ];
    }

    /**
     * Keyed per address and per client, so one account being hammered cannot
     * lock out everybody else.
     */
    public function throttleKey(): string
    {
        return mb_strtolower($this->string('email')->toString()).'|'.$this->ip();
    }
}
