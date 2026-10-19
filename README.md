# Currera PHP

PHP client for the Currera transactional email, transactional SMS, and subscriber
APIs. Uses fluent message builders and convenient response methods while
sending Currera's own API payloads.

Requires PHP 8.1 or newer with the cURL and JSON extensions.

## Installation

After this repository is published and registered on Packagist:

    composer require onebytesolutions/currera

Before Packagist registration, add the GitHub repository to your application's
composer.json and require the branch you publish (this example assumes main):

    {
        "repositories": [
            {"type": "vcs", "url": "https://github.com/onebytesolutions/currera"}
        ],
        "require": {
            "onebytesolutions/currera": "dev-main"
        }
    }

For local development, use a Composer path repository pointing to this directory.

## Configuration

    require __DIR__ . '/vendor/autoload.php';

    $currera = new \Currera\Client(
        getenv('CURRERA_API_KEY'),
        'https://currera.example.com',
        timeout: 30,
        connectTimeout: 10
    );

Use your installation URL, including its subdirectory if applicable, without
the /api suffix. The client appends the endpoint path. API keys use bearer
authentication. Use HTTPS for deployed installations and keep keys outside
source control.

Assign the service account a role with only the API permissions it needs:

| Operation | Permission |
| --- | --- |
| Send transactional email | transactional_email.send |
| Send transactional SMS | transactional_sms.send |
| Add a subscriber/list membership | subscribers.create |
| Update subscriber profile fields | subscribers.edit |

These permissions require Currera migration 017_api_operation_permissions.php.
Existing role access is preserved during migration; review service-account
roles to remove broader permissions when they are no longer needed.

## Send email

    $mail = (new \Currera\Mail())
        ->setTemplateId('YOUR_EMAIL_TEMPLATE_ID')
        ->setFrom('receipts@example.com', 'Receipts')
        ->setReplyTo('support@example.com')
        ->setSubject('Order {{ORDER_NUMBER}}')
        ->addTo('customer@example.com', 'Alex', ['FIRST_NAME' => 'Alex'])
        ->addDynamicTemplateData('ORDER_NUMBER', 'A-1007')
        ->setTracking(true, false);

    $response = $currera->send($mail);

Templates must be active email templates. IDs may be the template UUID, an
existing imported template identifier, or a numeric Currera template ID.
The subject defaults to the template name when omitted. The sender defaults to
the selected provider's first verified sender. Explicit senders must be verified
on that provider.

Call addTo repeatedly for multiple recipients (at most 100 per request).
Recipient placeholders override shared dynamic template data. Email placeholders
are escaped by Currera for HTML content.

## Send raw HTML or plain text

Use the same client method without selecting a template:

    $mail = (new \Currera\Mail())
        ->setFrom('receipts@example.com', 'Receipts')
        ->setSubject('Order {{ORDER_NUMBER}}')
        ->setHtml('<p>Hello {{NAME}}, your order is {{ORDER_NUMBER}}.</p>')
        ->setText('Hello {{NAME}}, your order is {{ORDER_NUMBER}}.')
        ->addTo('customer@example.com', 'Alex')
        ->addDynamicTemplateData('ORDER_NUMBER', 'A-1007');

    $response = $currera->send($mail);

Requires the updated Currera API and transactional_email.send permission.
Provide a subject and at least one non-empty body using setHtml() or setText().
Both can be supplied for multipart email; neither is generated automatically.
Do not combine raw bodies with setTemplateId(); the API rejects mixed requests
with 422 rather than silently selecting one source.

Raw bodies support shared and recipient placeholders. HTML placeholder values
are escaped; the HTML markup you supply becomes the email body. Verified sender
requirements, suppressions, optional tracking, and recipient results are the
same as for template sends. A raw send returns data.template_id as null.

Raw arrays also work: send a subject and html and/or text instead of template_id.

## Send SMS

    $sms = (new \Currera\Sms())
        ->setTemplateId('YOUR_SMS_TEMPLATE_ID')
        ->addTo('+12025550123', 'Alex')
        ->addDynamicTemplateData('ORDER_NUMBER', 'A-1007');

    $response = $currera->sendSms($sms);

Templates must be active SMS templates, identified by UUID or numeric ID.
Recipients must already be active Currera contacts with recorded SMS opt-in
and E.164 phone numbers. Sending does not create consent. SMS supports at most
100 recipients per request.

Optionally call setFrom('+12025550999') with a verified sending number on the
selected SMS provider. Otherwise Currera chooses its first verified sender.
SMS does not support open or click tracking.

## Provider selection

Both builders support setProviderId(2) or setProvider('driver-code').
The most recent provider setter replaces the previous selection. If omitted,
Currera chooses the default active provider for the channel.

Email drivers include amazon-ses, smtp, and log. SMS drivers include
twilio and log; actual availability depends on your installation.

## Subscribe a contact

    $response = $currera->subscribe(
        'a7c93e2b8f0146d5',
        'customer@example.com',
        [
            'first_name' => 'Alex',
            'last_name' => 'Smith',
            'bypass_double_opt_in' => false,
        ]
    );

Use the displayed 16-character List ID, not a numeric database ID. Names apply
to newly created contacts. Double opt-in is respected by default. Explicitly
setting bypass_double_opt_in to the boolean true activates membership without
a confirmation email, subject to server suppression and unsubscribe rules.
There is no unsubscribe endpoint in this client or the current REST API.

## Responses and errors

Successful requests return a Response with statusCode(), body(), headers(),
and json(). API failures throw Currera\Exception\ApiException:

    try {
        $response = $currera->send($mail);
        $result = $response->json();
    } catch (\Currera\Exception\ApiException $exception) {
        echo $exception->getMessage();
        echo $exception->statusCode(); // Also available through getCode().
        echo $exception->errorCode();  // For example: invalid_request.
        $response = $exception->response();
        // Inspect response()->json()['data']['messages'] for send outcomes.
    } catch (\Exception $exception) {
        // Includes transport, configuration, and request JSON encoding errors.
        echo $exception->getMessage();
    }

All non-2xx HTTP responses throw, including redirects, authentication failures,
validation failures, and server errors. Explicit API errors and failed recipients
also throw, even when the HTTP status is 202. Malformed or non-JSON responses
throw ApiException; their original body is available through response()->body().
A decoding failure is retained as getPrevious().

A partial send can accept some recipients and reject others. Its exception
retains data.messages in the response. Do not resend to accepted recipients.
Provider acceptance does not confirm final delivery. API responses use
partial_send_failure for mixed outcomes and send_failed when none were accepted.
Other unsuccessful HTTP responses without a structured error use http_error;
invalid successful responses use invalid_response.

Subscriptions return 201 when created or 200 for an existing subscription.
Transport failures throw RuntimeException. Invalid configuration throws
InvalidArgumentException. Request JSON encoding failures throw JsonException.
ApiException extends RuntimeException, so existing RuntimeException handlers
also catch API failures. Catch Exception to handle all these exception types.

This changes earlier client behavior that returned HTTP error Response objects.
Update integrations to catch exceptions instead of relying on status checks
after a failed call. The API now emits top-level data/error fields without page
layout wrappers, including invalid-key responses.

The client verifies TLS certificates, does not follow redirects, and never
retries automatically. A timeout may occur after a provider accepted a message;
repeating the request can duplicate a send.

## Raw payloads

send() and sendSms() also accept arrays for payloads supported by your Currera
installation:

    $response = $currera->send([
        'template_id' => 'YOUR_EMAIL_TEMPLATE_ID',
        'addTo' => [['email' => 'customer@example.com']],
        'placeholders' => (object) ['ORDER_NUMBER' => 'A-1007'],
    ]);

Use JSON objects for placeholder maps, especially maps with numeric keys.
Builders handle this conversion automatically. Server-side validation remains
authoritative; the client does not resolve templates, providers, or permissions.

## Package layout

- src/Client.php: connection configuration and endpoint requests.
- src/Mail.php: template and raw HTML/plain-text email builder.
- src/Sms.php: SMS message builder.
- src/TemplateMessage.php: shared template, provider, and recipient behavior.
- src/Response.php: HTTP status, raw headers, body, and JSON access.
- examples/: email, SMS, and subscription scripts using environment credentials.

Load vendor/autoload.php to use the package.

## Release and verification

Publish this library repository as onebytesolutions/currera, then register it on
Packagist and create semantic version tags (for example v1.0.0). Composer derives
versions from tags; composer.json intentionally has no hard-coded version.

Before release, validate Composer metadata and autoloading and exercise the
builders, HTTP errors, malformed responses, authentication, subdirectory URLs,
timeouts, and mixed recipient results against a development Currera installation.
Run send examples only with intended recipients and configured providers.

## License

MIT. Copyright (c) 2026 Jason Bryan, One Byte Solutions, LLC. See LICENSE.

## Update subscriber profile values

Requires a Currera installation with POST /api/subscriber-fields support and an
API key with subscribers.edit permission.

    $response = $currera->updateSubscriberFields('customer@example.com', [
        'company' => 'Acme',
        'loyalty_points' => 120,
        'renewal_date' => '2026-12-31',
        'vip' => true,
    ]);

Adds or replaces values on an existing subscriber using exact slugs of fields
already defined in Currera. It does not create contacts or field definitions.
Omitted fields remain unchanged. Null or an empty string clears an optional
stored value; configured defaults may then apply. Required fields cannot be
cleared. Consent and membership states remain unchanged.

Values must be scalar or null. Dates require YYYY-MM-DD; dropdown/radio fields
require configured options; checkboxes accept booleans or 0/1.
Unknown slugs and invalid values throw ApiException with status 422 without applying any changes.
A missing subscriber throws ApiException with status 404. Success returns 200 with data.subscriber_id,
data.email, and data.updated_fields.

Profile values persist separately from message placeholders. For transactional
messages, supply template values with addDynamicTemplateData() or addTo()
placeholders; updating a profile does not copy values into a transactional send.
