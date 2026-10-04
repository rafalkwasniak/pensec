<?php

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Support\ApiResponse;
use App\Support\JsonOutline;
use App\Support\UploadedReport;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads a report submission off the wire, plain or gzip-compressed.
 *
 * The body is streamed, inflated chunk by chunk and measured as it grows, so an
 * oversized document - or a gzip bomb - is refused before it is ever held
 * whole. What passes is held exactly once: validated with json_validate(),
 * which builds no PHP values, and outlined by JsonOutline for the few fields the
 * API reads. Nothing here decodes the report; that is what exhausted memory.
 *
 * The request must have been captured by App\Http\IncomingRequest, otherwise
 * Laravel has already decoded a plain JSON body before this runs.
 */
class StreamReportUpload
{
    /** Small enough that one inflated chunk cannot overshoot the limit by much. */
    private const CHUNK_BYTES = 8192;

    public function handle(Request $request, Closure $next): Response
    {
        $encoding = strtolower(trim((string) $request->header('Content-Encoding')));

        if (! in_array($encoding, ['', 'identity', 'gzip'], true)) {
            return ApiResponse::error(ApiErrorCode::UnsupportedEncoding, __('api.errors.unsupported_encoding'), 415);
        }

        $document = $this->read($request->getContent(true), $encoding === 'gzip');

        if ($document === null) {
            return ApiResponse::error(ApiErrorCode::PayloadTooLarge, __('api.errors.payload_too_large'), 413);
        }

        if (! json_validate($document) || JsonOutline::type($document) !== 'object') {
            throw ValidationException::withMessages(['body' => __('api.errors.body_not_object')]);
        }

        $request->attributes->set(UploadedReport::class, UploadedReport::fromDocument($document));

        return $next($request);
    }

    /**
     * The uncompressed body, or null when it is larger than the limit allows.
     *
     * Chunks go to a temporary file rather than onto a growing string: growing
     * a string reallocates it, briefly holding two copies, and reading the file
     * back allocates the final size once.
     *
     * @param  resource  $body
     */
    private function read($body, bool $gzip): ?string
    {
        $limit = (int) config('pensec.reports.max_payload_bytes');
        $inflate = $gzip ? inflate_init(ZLIB_ENCODING_GZIP) : null;
        $buffer = tmpfile();
        $received = 0;
        $size = 0;

        while (! feof($body)) {
            $chunk = fread($body, self::CHUNK_BYTES);

            if ($chunk === false || $chunk === '') {
                break;
            }

            $received += strlen($chunk);

            if ($inflate !== null) {
                $chunk = $this->inflateMembers($inflate, $chunk);
            }

            $size += strlen($chunk);

            if ($received > $limit || $size > $limit) {
                return null;
            }

            fwrite($buffer, $chunk);
        }

        // A small body ends its gzip stream inside the loop already, and zlib
        // refuses to finish a stream that is finished.
        if ($inflate !== null && inflate_get_status($inflate) !== ZLIB_STREAM_END) {
            $tail = $this->inflate($inflate, '', ZLIB_FINISH);

            // A stream cut off mid-way inflates without complaint until here.
            if (inflate_get_status($inflate) !== ZLIB_STREAM_END) {
                $this->corrupt();
            }

            if ($size + strlen($tail) > $limit) {
                return null;
            }

            fwrite($buffer, $tail);
        }

        rewind($buffer);

        return (string) stream_get_contents($buffer);
    }

    /**
     * Inflates one chunk, carrying on into the next gzip member when one ends
     * mid-chunk. A gzip file may be several members back to back - `cat a.gz
     * b.gz`, or what pigz writes - and zlib stops at the end of the first;
     * reading on is what keeps the rest from being dropped without a word.
     * Bytes after the end that are not another member fail as corrupt.
     *
     * @param  \InflateContext  $context  replaced by a fresh one when a new member starts
     */
    private function inflateMembers(&$context, string $chunk): string
    {
        $out = '';

        while ($chunk !== '') {
            if (inflate_get_status($context) === ZLIB_STREAM_END) {
                $context = inflate_init(ZLIB_ENCODING_GZIP);
            }

            $before = inflate_get_read_len($context);
            $out .= $this->inflate($context, $chunk, ZLIB_SYNC_FLUSH);

            $chunk = inflate_get_status($context) === ZLIB_STREAM_END
                ? substr($chunk, inflate_get_read_len($context) - $before)
                : '';
        }

        return $out;
    }

    /**
     * @param  \InflateContext  $context
     */
    private function inflate($context, string $chunk, int $mode): string
    {
        $inflated = @inflate_add($context, $chunk, $mode);

        if ($inflated === false) {
            $this->corrupt();
        }

        return $inflated;
    }

    private function corrupt(): never
    {
        throw ValidationException::withMessages(['body' => __('api.errors.body_not_gzip')]);
    }
}
