<?php

namespace App\Http\Requests\Api\V1;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SubmitReportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_id' => ['required', 'uuid'],
            'report' => [
                'required',
                'array',
                // A JSON list decodes to a PHP array just like an object does,
                // and the contract promises an object.
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_array($value) && $value !== [] && array_is_list($value)) {
                        $fail('The report must be a JSON object.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'report_id.uuid' => 'The report id must be a valid UUID.',
            'report.array' => 'The report must be a JSON object.',
        ];
    }

    public function reportId(): string
    {
        return $this->string('report_id')->toString();
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return $this->array('report');
    }
}
