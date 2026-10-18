<?php

/**
 * @author Jason Bryan, One Byte Solutions
 * @link https://OneByteSolutions.com
 * @link https://Currera.org
 * Do you like this tool? You can support development by buying me a coffee: https://buymeacoffee.com/sermos
 */

namespace Currera;

/**
 * Synchronous, bearer-authenticated client for a self-hosted Currera installation.
 *
 * HTTP error statuses are returned as Response objects. Transport and JSON
 * serialization failures throw exceptions. Requests are never retried because
 * a failed connection does not prove that a message was not already sent.
 *
 * Payload validation, authorization, and recipient eligibility are enforced
 * by the server; builders do not preflight these checks.
 */
class Client {

    /** @var string Installation URL, including any subdirectory, without a trailing slash. */
    private string $baseUrl;

    /**
     * Configure the installation and request time limits.
     *
     * @param string $apiKey Service-account API key, sent unchanged as a bearer token.
     * @param string $baseUrl HTTP(S) installation URL without /api, credentials, query, or fragment.
     * @param int $timeout Total request timeout in positive seconds.
     * @param int $connectTimeout Connection timeout in positive seconds.
     * @throws \InvalidArgumentException If the URL, API key, or timeouts are invalid.
     */
    public function __construct(
        private string $apiKey,
        string $baseUrl,
        private int $timeout = 30,
        private int $connectTimeout = 10
    ) {
        $parts = parse_url($baseUrl);
        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new \InvalidArgumentException('Use an HTTP(S) installation URL without credentials, query, or fragment.');
        }
        if (trim($apiKey) === '' || strpbrk($apiKey, "\r\n") !== false) {
            throw new \InvalidArgumentException('A valid API key is required.');
        }
        if ($timeout < 1 || $connectTimeout < 1) {
            throw new \InvalidArgumentException('Timeouts must be positive seconds.');
        }
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Send an active stored email template through POST /api/transactional.
     *
     * Requires campaigns.send. The server accepts at most 100 recipients.
     * HTTP 202 means at least one provider acceptance, not final delivery;
     * inspect data.messages for each recipient's accepted flag.
     *
     * @param Mail|array<string, mixed> $mail Builder or raw Currera API payload.
     * @return Response HTTP result, including authorization and validation errors.
     * @throws \JsonException If the payload cannot be encoded as JSON.
     * @throws \RuntimeException If cURL is unavailable or the transport fails.
     */
    public function send(Mail|array $mail): Response {
        return $this->post('/api/transactional', $mail instanceof Mail ? $mail->jsonSerialize() : $mail);
    }

    /**
     * Send an active stored SMS template through POST /api/transactional-sms.
     *
     * Requires campaigns.send. Recipients must be active contacts with recorded
     * SMS opt-in and E.164 phone numbers. HTTP 202 can contain mixed recipient
     * results and does not confirm delivery. SMS tracking is unsupported.
     *
     * @param Sms|array<string, mixed> $sms Builder or raw Currera API payload.
     * @return Response HTTP result; inspect data.messages for individual outcomes.
     * @throws \JsonException If the payload cannot be encoded as JSON.
     * @throws \RuntimeException If cURL is unavailable or the transport fails.
     */
    public function sendSms(Sms|array $sms): Response {
        return $this->post('/api/transactional-sms', $sms instanceof Sms ? $sms->jsonSerialize() : $sms);
    }


    /**
     * Add a list membership through POST /api/subscribers.
     *
     * Requires lists.edit. Double opt-in is respected unless explicitly bypassed.
     * Names apply to new contacts. Suppression and unsubscribe rules still apply.
     * Explicit arguments take precedence over list_id/email entries in options.
     *
     * @param string $listId Displayed 16-character lowercase hexadecimal List ID.
     * @param string $email Subscriber email address.
     * @param array{first_name?: string, last_name?: string, bypass_double_opt_in?: bool} $options Subscription options.
     * @return Response HTTP 201 for creation or 200 for an existing subscription, or an error.
     * @throws \JsonException If the payload cannot be encoded as JSON.
     * @throws \RuntimeException If cURL is unavailable or the transport fails.
     */
    public function subscribe(string $listId, string $email, array $options = []): Response {
        return $this->post('/api/subscribers', array_merge($options, [
                    'list_id' => $listId,
                    'email' => $email,
        ]));
    }

    /**
     * Add or replace existing subscriber profile values by exact field slug.
     *
     * Calls POST /api/subscriber-fields and requires lists.edit. This creates
     * neither contacts nor field definitions. Omitted fields remain unchanged.
     * Null or an empty string clears an optional stored value; a configured
     * default may then apply. Required fields cannot be cleared. Invalid input
     * rejects the entire update. Consent and memberships are unaffected.
     *
     * Profile values persist separately from transactional message placeholders.
     *
     * @param string $email Email identifying the existing subscriber.
     * @param array<string, string|int|float|bool|null> $fields Non-empty map of slugs to values.
     * @return Response HTTP 200 with data.updated_fields, 404 for a missing subscriber, or an error.
     * @throws \JsonException If the payload cannot be encoded as JSON.
     * @throws \RuntimeException If cURL is unavailable or the transport fails.
     */
    public function updateSubscriberFields(string $email, array $fields): Response {
        return $this->post('/api/subscriber-fields', [
            'email' => $email,
            'custom_fields' => (object) $fields,
        ]);
    }

    /**
     * Encode and POST one JSON object without redirects or automatic retries.
     *
     * TLS verification remains enabled. Keeping redirects disabled prevents the
     * bearer token from being forwarded to a different destination.
     *
     * @param string $path Internal endpoint path beginning with a slash.
     * @param array<string, mixed> $payload Request fields; nested objects are preserved.
     * @return Response Raw HTTP result without interpreting the status or body.
     * @throws \JsonException If JSON encoding fails before the request.
     * @throws \RuntimeException If cURL cannot initialize or execute the request.
     */
    private function post(string $path, array $payload): Response {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('The Currera client requires the PHP cURL extension.');
        }
        $body = json_encode((object) $payload, JSON_THROW_ON_ERROR);
        $handle = curl_init($this->baseUrl . $path);
        if ($handle === false) {
            throw new \RuntimeException('Could not initialize the Currera API connection.');
        }
        $headers = [];
        try {
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'User-Agent: Currera-PHP/1.0',
                ],
                CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
                    // Discard interim header blocks, retaining the final response headers.
                    if (str_starts_with($line, 'HTTP/')) {
                        $headers = [];
                    }
                    $headers[] = rtrim($line, "\r\n");
                    return strlen($line);
                },
            ]);
            $result = curl_exec($handle);
            if ($result === false) {
                // Do not retry automatically: the server may already have sent the message.
                throw new \RuntimeException('Currera API transport failed (cURL code ' . curl_errno($handle) . ').');
            }
            return new Response((int) curl_getinfo($handle, CURLINFO_HTTP_CODE), $result, $headers);
        } finally {
            curl_close($handle);
        }
    }
}

