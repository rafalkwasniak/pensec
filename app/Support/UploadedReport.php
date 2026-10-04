<?php

namespace App\Support;

/**
 * A submission as it arrived: the body exactly as sent (gzip removed), plus the
 * handful of things the API needs to know about it. Built once, by the upload
 * middleware, so nothing downstream ever reads the body again or decodes it.
 */
final readonly class UploadedReport
{
    private function __construct(
        public string $document,
        public string $sha256,
        public mixed $reportId,
        public ?string $reportType,
        public ?string $scanTime,
    ) {}

    /**
     * @param  string  $document  JSON that json_validate() accepted and whose top level is an object
     */
    public static function fromDocument(string $document): self
    {
        $members = JsonOutline::members($document, ['report_id', 'report']);

        $reportId = isset($members['report_id']) ? JsonOutline::scalar($document, $members['report_id']) : null;
        $reportType = isset($members['report']) ? JsonOutline::type($document, $members['report']) : null;

        $scanTime = null;

        if ($reportType === 'object') {
            $at = JsonOutline::members($document, ['scan_time'], $members['report'])['scan_time'] ?? null;
            $value = $at !== null ? JsonOutline::scalar($document, $at, 64) : null;
            $scanTime = is_string($value) ? $value : null;
        }

        return new self($document, hash('sha256', $document), $reportId, $reportType, $scanTime);
    }

    public function bytes(): int
    {
        return strlen($this->document);
    }
}
