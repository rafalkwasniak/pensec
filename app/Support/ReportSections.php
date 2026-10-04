<?php

namespace App\Support;

use App\Enums\PayloadFormat;
use App\Models\Report;
use App\Services\ReportFacts;

/**
 * Reads the parts of a stored report that ReportFacts uses, and nothing else.
 *
 * A whole json_decode costs about 5.6 times the document, so a report of a few
 * hundred megabytes cannot be decoded in one piece within memory_limit. Here
 * the document is held once as a string and only the sections listed in
 * ReportFacts::SECTIONS are decoded, each on its own. The one section that
 * grows with the network - nuclei's findings, tens of thousands of entries - is
 * not decoded at all: it is handed over as a generator yielding one entry at a
 * time, which ReportFacts folds into a summary as it goes.
 */
class ReportSections
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Report $report): array
    {
        $stream = $report->payloadStream();
        $json = (string) stream_get_contents($stream);
        fclose($stream);

        if (! json_validate($json)) {
            return [];
        }

        return self::fromJson($json, $report->payload_format === PayloadFormat::Submission);
    }

    /**
     * @param  string  $json  a document json_validate() accepted
     * @param  bool  $envelope  whether the report sits under a `report` key
     * @return array<string, mixed>
     */
    public static function fromJson(string $json, bool $envelope = false): array
    {
        $root = $envelope ? (JsonOutline::members($json, ['report'])['report'] ?? null) : 0;

        if ($root === null || JsonOutline::type($json, $root) !== 'object') {
            return [];
        }

        $sections = [];

        foreach (JsonOutline::members($json, ReportFacts::SECTIONS, $root) as $key => $offset) {
            $sections[$key] = $key === 'nuclei_results'
                ? self::nuclei($json, $offset)
                : JsonOutline::decode($json, $offset);
        }

        return $sections;
    }

    /**
     * Nuclei in the probe's normalised form keeps everything but its findings;
     * those become a lazy sequence. Any older shape is small and decoded whole.
     */
    private static function nuclei(string $json, int $offset): mixed
    {
        $members = JsonOutline::members($json, ['representation', 'templates', 'targets', 'scans', 'findings'], $offset);

        if (! isset($members['representation'], $members['findings'])) {
            return JsonOutline::decode($json, $offset);
        }

        $section = [];

        foreach (['representation', 'templates', 'targets', 'scans'] as $key) {
            if (isset($members[$key])) {
                $section[$key] = JsonOutline::decode($json, $members[$key]);
            }
        }

        $findings = $members['findings'];

        $section['findings'] = (function () use ($json, $findings): \Generator {
            foreach (JsonOutline::elements($json, $findings) as $at) {
                yield JsonOutline::decode($json, $at);
            }
        })();

        return $section;
    }
}
