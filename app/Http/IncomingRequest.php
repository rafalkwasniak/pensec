<?php

namespace App\Http;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * The request the front controller captures.
 *
 * Laravel decodes every JSON body while it builds the request, before any
 * middleware runs - for a report that is the whole document at about 5.6 times
 * its size, plus a copy of the raw body, and it was the first thing to exhaust
 * memory_limit. A report submission is read by its own middleware as a stream,
 * so for that one route the decoded input is left empty and the body is never
 * pulled into memory here.
 */
class IncomingRequest extends Request
{
    public function json($key = null, $default = null)
    {
        if (! isset($this->json) && $this->isReportSubmission()) {
            $this->json = new InputBag;
        }

        return parent::json($key, $default);
    }

    /**
     * The body as a string is withheld for the same route. Laravel asks for it
     * again when it builds the FormRequest (Request::createFrom), which would
     * hold a second copy of the whole upload; StreamReportUpload reads it as a
     * stream - getContent(true) - and nothing else may.
     */
    public function getContent(bool $asResource = false)
    {
        if (! $asResource && $this->isReportSubmission()) {
            return '';
        }

        return parent::getContent($asResource);
    }

    public function isReportSubmission(): bool
    {
        return $this->getRealMethod() === 'POST' && rtrim($this->getPathInfo(), '/') === '/api/v1/reports';
    }
}
