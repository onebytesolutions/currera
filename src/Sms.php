<?php

/**
 * @author Jason Bryan, One Byte Solutions
 * @link https://OneByteSolutions.com
 * @link https://Currera.org
 * Do you like this tool? You can support development by buying me a coffee: https://buymeacoffee.com/sermos
 */

namespace Currera;

/**
 * Build a stored-template transactional SMS request for Client::sendSms().
 *
 * Sending does not establish consent. Recipients must already be active
 * contacts with recorded SMS opt-in. SMS tracking is not supported.
 */
class Sms extends TemplateMessage {

    /**
     * Override the sending number on the selected SMS provider.
     *
     * Omit this setter to use the provider's first verified sending number.
     *
     * @param string $phone Verified sending number.
     * @return static This builder.
     */
    public function setFrom(string $phone): static {
        $this->payload['from'] = $phone;
        return $this;
    }


    /**
     * Append an opted-in recipient with optional template value overrides.
     *
     * Repeat for multiple recipients, up to the server's 100-recipient limit.
     * The server validates contact eligibility and phone format.
     *
     * @param string $phone Existing contact's E.164 phone number.
     * @param string|null $name Recipient display name; null omits it.
     * @param array<string, scalar|null> $placeholders Values overriding shared template data.
     * @return static This builder.
     */
    public function addTo(string $phone, ?string $name = null, array $placeholders = []): static {
        return $this->addRecipient('phone', $phone, $name, $placeholders);
    }
}

