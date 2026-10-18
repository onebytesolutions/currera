<?php

/**
 * @author Jason Bryan, One Byte Solutions
 * @link https://OneByteSolutions.com
 * @link https://Currera.org
 * Do you like this tool? You can support development by buying me a coffee: https://buymeacoffee.com/sermos
 */

namespace Currera;

/**
 * HTTP response preserving the status, unparsed body, and raw header lines.
 *
 * Represents both successful and failed HTTP requests. Construction does not
 * decode JSON or throw on an error status. A successful send status indicates
 * provider acceptance, not confirmed delivery.
 */
class Response {

    /**
     * @param int $status HTTP status code reported by the transport.
     * @param string $content Raw response body, including non-JSON error pages.
     * @param list<string> $responseHeaders Final response header block as raw lines.
     */
    public function __construct(private int $status, private string $content, private array $responseHeaders) {
        
    }

    /**
     * Return the HTTP status without interpreting application-level outcomes.
     *
     * @return int HTTP status code.
     */
    public function statusCode(): int {
        return $this->status;
    }

    /**
     * Return the body unchanged for logging or handling non-JSON responses.
     *
     * @return string Raw response body.
     */
    public function body(): string {
        return $this->content;
    }


    /**
     * Return raw header lines, not a map of header names to values.
     *
     * Includes the status line and may include empty separator lines.
     * Repeated headers retain their original order.
     *
     * @return list<string> Final response header block without line endings.
     */
    public function headers(): array {
        return $this->responseHeaders;
    }

    /**
     * Decode the body into associative arrays on each call.
     *
     * Does not inspect HTTP status or validate the API response schema.
     * Empty bodies, HTML responses, and JSON deeper than 64 levels fail decoding.
     *
     * @return array<array-key, mixed> Decoded JSON object or array.
     * @throws \JsonException If the body is not valid JSON within the depth limit.
     * @throws \UnexpectedValueException If valid JSON decodes to a scalar or null.
     */
    public function json(): array {
        $decoded = json_decode($this->content, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new \UnexpectedValueException('The API response is not a JSON object or array.');
        }
        return $decoded;
    }
}

