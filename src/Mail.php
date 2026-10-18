<?php

/**
 * @author Jason Bryan, One Byte Solutions
 * @link https://OneByteSolutions.com
 * @link https://Currera.org
 * Do you like this tool? You can support development by buying me a coffee: https://buymeacoffee.com/sermos
 */

namespace Currera;

/**
 * Build a stored-template transactional email request for Client::send().
 *
 * Sender verification, template availability, and recipient validation happen
 * on the server. Omitted sender and subject values use server defaults.
 */
class Mail extends TemplateMessage {

    /**
     * Override the sender using a verified address on the selected provider.
     *
     * Passing null for the name removes an earlier name override. An existing
     * reply-to override is preserved.
     *
     * @param string $email Verified sender email address.
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

