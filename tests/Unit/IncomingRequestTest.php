<?php

namespace Tests\Unit;

use App\Http\IncomingRequest;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * The test client builds requests through Illuminate\Http\Request, not through
 * the front controller, so this is the only place IncomingRequest is exercised.
 */
class IncomingRequestTest extends TestCase
{
    private function capture(string $method, string $uri, string $body): IncomingRequest
    {
        return IncomingRequest::createFromBase(
            SymfonyRequest::create($uri, $method, server: ['CONTENT_TYPE' => 'application/json'], content: $body),
        );
    }

    public function test_a_report_submission_is_not_decoded_on_capture(): void
    {
        $request = $this->capture('POST', '/api/v1/reports', '{"report_id": "r1", "report": {}}');

        $this->assertSame([], $request->request->all());
        $this->assertSame([], $request->json()->all());
        $this->assertSame('{"report_id": "r1", "report": {}}', stream_get_contents($request->getContent(true)));
    }

    /**
     * Building the FormRequest copies the request through createFrom(), which
     * asks for the body as a string - a second copy of a report of hundreds of
     * megabytes, unless that answer is withheld.
     */
    public function test_building_a_form_request_does_not_copy_the_body(): void
    {
        $request = $this->capture('POST', '/api/v1/reports', '{"report_id": "r1", "report": {}}');

        $copy = Request::createFrom($request);

        $this->assertSame('', $copy->getContent());
        $this->assertSame('{"report_id": "r1", "report": {}}', stream_get_contents($request->getContent(true)));
    }

    public function test_every_other_json_request_is_decoded_as_usual(): void
    {
        $this->assertSame(['a' => 1], $this->capture('POST', '/api/v1/other', '{"a": 1}')->request->all());
        $this->assertSame(['a' => 1], $this->capture('PUT', '/api/v1/reports', '{"a": 1}')->request->all());
    }
}
