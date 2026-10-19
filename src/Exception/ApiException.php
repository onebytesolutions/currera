<?php

/**
 * @author Jason Bryan, One Byte Solutions
 * @link https://OneByteSolutions.com
 * @link https://Currera.org
 * Do you like this tool? You can support development by buying me a coffee: https://buymeacoffee.com/sermos
 */

namespace Currera\Exception;

use Currera\Response;

/**
 * An unsuccessful HTTP/API response, including partial recipient failures.
 *
 * The original response is retained for inspecting recipient outcomes or a
 * non-JSON proxy error. The inherited exception code is the HTTP status.
 * This exception does not imply that no recipients were sent a message.
 */
class ApiException extends \RuntimeException {

    /**
     * @param string $message API error message or a safe descriptive fallback.
     * @param Response $response Original HTTP response, without transformation.
     * @param string|null $apiErrorCode Machine-readable API error identifier.
     * @param \Throwable|null $previous Underlying response decoding failure.
     */
    public function __construct(
        string $message,
        private Response $response,
        private ?string $apiErrorCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $response->statusCode(), $previous);
    }

    /** Return the HTTP status, including 202 for a partially accepted send. */
    public function statusCode(): int {
        return $this->response->statusCode();
    }

    /** Return the machine-readable API error code, if available. */
    public function errorCode(): ?string {
        return $this->apiErrorCode;
    }

    /** Return the original response, including per-recipient results. */
    public function response(): Response {
        return $this->response;
    }
}
