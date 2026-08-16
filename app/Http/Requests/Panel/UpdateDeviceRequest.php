<?php

namespace App\Http\Requests\Panel;

use App\Enums\DeviceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(DeviceStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('panel.validation.name_required'),
            'name.max' => __('panel.validation.name_max'),
            'status.required' => __('panel.validation.status_required'),
            'status.enum' => __('panel.validation.status_invalid'),
        ];
    }
}
