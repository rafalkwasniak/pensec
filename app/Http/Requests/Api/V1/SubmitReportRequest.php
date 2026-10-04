<?php

namespace App\Http\Requests\Api\V1;

use App\Support\UploadedReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the envelope of a submission. The body itself was read and checked
 * by StreamReportUpload; what is validated here is the outline it took, never a
 * decoded report.
 */
class SubmitReportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        $upload = $this->upload();

        return array_filter([
            'report_id' => $upload->reportId,
            'report' => $upload->reportType,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_id' => ['required', 'uuid'],
            'report' => ['required', Rule::in(['object'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'report_id.uuid' => __('api.errors.report_id_uuid'),
            'report.in' => __('api.errors.report_not_object'),
        ];
    }

    public function upload(): UploadedReport
    {
        return $this->attributes->get(UploadedReport::class);
    }

    public function reportId(): string
    {
        return (string) $this->upload()->reportId;
    }
}
