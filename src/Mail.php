<?php

/**
 * @author Jason Bryan, One Byte Solutions
 * @link https://OneByteSolutions.com
 * @link https://Currera.org
 * Do you like this tool? You can support development by buying me a coffee: https://buymeacoffee.com/sermos
 */

namespace Currera;

/**
 * Build a template-based or raw-content email request for Client::send().
 *
 * Sender verification, template availability, and recipient validation happen
 * on the server. Omitted senders use server defaults. Raw content requires a
 * subject; template sends may use the saved template subject.
 */
class Mail extends TemplateMessage {

    /**
     * Set caller-provided HTML instead of using a stored template.
     *
     * Requires a subject and must not be combined with setTemplateId().
     * Shared and recipient placeholders are supported; placeholder values are
     * HTML-escaped by Currera. The supplied markup itself is the email content.
     *
     * @param string $html HTML body, optionally containing placeholder tags.
     * @return static This builder.
     */
    public function setHtml(string $html): static {
        $this->payload['html'] = $html;
        return $this;
    }

    /**
     * Set plain-text content, alone or as an alternative to caller-provided HTML.
     *
     * Requires a subject and must not be combined with setTemplateId().
     * No plain-text alternative is automatically generated from HTML.
     *
     * @param string $text Plain-text body, optionally containing placeholder tags.
     * @return static This builder.
     */
    public function setText(string $text): static {
        $this->payload['text'] = $text;
        return $this;
    }

    /**
     * Use a verified address or an address on a verified domain for this provider.
     *
     * Passing null for the name removes an earlier name override. An existing
     * reply-to override is preserved. Domain verification covers the exact domain,
     * not its subdomains. The address need not have a saved mailbox profile.
     *
     * @param string $email Individually verified address or mailbox on a verified domain.
     * @param string|null $name Optional sender display name.
     * @return static This builder.
     */
    public function setFrom(string $email, ?string $name = null): static {
        $this->payload['from']['email'] = $email;
        if ($name !== null) {
            $this->payload['from']['name'] = $name;
        } else {
            unset($this->payload['from']['name']);
        }
        return $this;
    }

    /**
     * Override the reply-to without changing the saved sender profile.
     *
     * @param string $email Address that should receive replies.
     * @return static This builder.
     */
    public function setReplyTo(string $email): static {
        $this->payload['from']['reply_to'] = $email;
        return $this;
    }

    /**
     * Override the subject; template placeholders are resolved by the server.
     *
     * @param string $subject Subject text, optionally containing placeholder tags.
     * @return static This builder.
     */
    public function setSubject(string $subject): static {
        $this->payload['subject'] = $subject;
        return $this;
    }

    /**
     * Override the template preview text for {{preview-text}} / {{PREVIEW_TEXT}}.
     *
     * The email body must contain the tag; no preheader markup is injected.
     *
     * @param string $text Preview text, optionally containing recipient placeholders.
     * @return static This builder.
     */
    public function setPreviewText(string $text): static {
        $this->payload['preview_text'] = $text;
        return $this;
    }
    /**
     * Append an email recipient with optional template value overrides.
     *
     * Repeat for multiple recipients, up to the server's 100-recipient limit.
     * HTML placeholder escaping is performed by Currera.
     *
     * @param string $email Recipient email address.
     * @param string|null $name Recipient display name; null omits it.
     * @param array<string, scalar|null> $placeholders Values overriding shared template data.
     * @return static This builder.
     */
    public function addTo(string $email, ?string $name = null, array $placeholders = []): static {
        return $this->addRecipient('email', $email, $name, $placeholders);
    }

    /**
     * Configure Currera's transactional open and click tracking.
     *
     * @param bool $opens Enable open tracking.
     * @param bool|null $clicks Enable click tracking; null uses the opens setting.
     * @return static This builder.
     */
    public function setTracking(bool $opens, ?bool $clicks = null): static {
        $this->payload['tracking'] = ['opens' => $opens, 'clicks' => $clicks ?? $opens];
        return $this;
    }
}

