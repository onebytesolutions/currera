<?php

/**
 * @author Jason Bryan, One Byte Solutions
 * @link https://OneByteSolutions.com
 * @link https://Currera.org
 * Do you like this tool? You can support development by buying me a coffee: https://buymeacoffee.com/sermos
 */

namespace Currera;

/**
 * Mutable fluent builder for template selection, providers, and personalization.
 *
 * Setters replace individual values; addTo() appends recipients. Serialization
 * produces Currera's API field names and does not validate server resources.
 * Template placeholders affect a send and do not update subscriber profiles.
 */
abstract class TemplateMessage implements \JsonSerializable {

    /** @var array<string, mixed> Request fields accumulated by the builder. */
    protected array $payload = [];

    /**
     * Select an active template for this message's channel.
     *
     * @param string|int $templateId Stable template identifier or numeric record ID.
     * @return static This builder.
     */
    public function setTemplateId(string|int $templateId): static {
        $this->payload['template_id'] = $templateId;
        return $this;
    }

    /**
     * Select a provider by record ID, replacing any provider alias.
     *
     * @param int $providerId Active provider ID for this message's channel.
     * @return static This builder.
     */
    public function setProviderId(int $providerId): static {
        unset($this->payload['provider']);
        $this->payload['provider_id'] = $providerId;
        return $this;
    }

    /**
     * Select a provider by driver code or ID, replacing provider_id.
     *
     * Omit both provider setters to use the server's default active provider.
     *
     * @param string|int $provider Provider driver code or record ID.
     * @return static This builder.
     */
    public function setProvider(string|int $provider): static {
        unset($this->payload['provider_id']);
        $this->payload['provider'] = $provider;
        return $this;
    }

    /**
     * Set a shared template placeholder; repeating a key replaces its value.
     *
     * Recipient placeholders override shared values. The API expects scalar
     * values or null; unsupported values are rejected by encoding or the server.
     *
     * @param string $key Placeholder key, without template delimiters.
     * @param mixed $value Prefer a string, number, boolean, or null.
     * @return static This builder.
     */
    public function addDynamicTemplateData(string $key, mixed $value): static {
        $this->payload['placeholders'][$key] = $value;
        return $this;
    }

    /**
     * Export the payload without changing the builder.
     *
     * Placeholder maps are JSON objects, including when their keys are numeric.
     *
     * @return array<string, mixed> Fields suitable for JSON encoding.
     */
    public function jsonSerialize(): array {
        $payload = $this->payload;
        if (isset($payload['placeholders'])) {
            $payload['placeholders'] = (object) $payload['placeholders'];
        }
        return $payload;
    }

    /**
     * Append a recipient; deduplication and eligibility remain server concerns.
     *
     * @param string $field Channel address key: email or phone.
     * @param string $address Recipient email address or E.164 phone number.
     * @param string|null $name Display name; null omits the name field.
     * @param array<string, scalar|null> $placeholders Recipient overrides for shared template values.
     * @return static This builder.
     */
    protected function addRecipient(string $field, string $address, ?string $name, array $placeholders): static {
        $recipient = [$field => $address];
        if ($name !== null) {
            $recipient['name'] = $name;
        }
        if ($placeholders !== []) {
            $recipient['placeholders'] = (object) $placeholders;
        }
        $this->payload['addTo'][] = $recipient;
        return $this;
    }
}

