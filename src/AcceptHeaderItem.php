<?php

namespace Henrik\Http;

/**
 * Represents an Accept-* header item.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 */
class AcceptHeaderItem
{
    private string $value;
    private float $quality    = 1.0;
    private int $index        = 0;
    private array $attributes = [];

    public function __construct(string $value, array $attributes = [])
    {
        $this->value = $value;
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Returns header value's string representation.
     */
    public function __toString(): string
    {
        $string = $this->value . ($this->quality < 1 ? ';q=' . $this->quality : '');
        if (\count($this->attributes) > 0) {
            $string .= '; ' . HeaderUtils::toString($this->attributes, ';');
        }

        return $string;
    }

    /**
     * Builds an AcceptHeaderInstance instance from a string.
     *
     * @param ?string $itemValue
     */
    public static function fromString(?string $itemValue): self
    {
        $parts = HeaderUtils::split($itemValue ?? '', ';=');

        $part       = array_shift($parts);
        $attributes = HeaderUtils::combine($parts);

        return new self($part[0], $attributes);
    }

    /**
     * Set the item value.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Returns the item value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Set the item quality.
     *
     * @param float $quality
     *
     * @return $this
     */
    public function setQuality(float $quality): static
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * Returns the item quality.
     */
    public function getQuality(): float
    {
        return $this->quality;
    }

    /**
     * Set the item index.
     *
     * @param int $index
     *
     * @return $this
     */
    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Returns the item index.
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * Tests if an attribute exists.
     *
     * @param string $name
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Returns an attribute by its name.
     *
     * @param string     $name
     * @param mixed|null $default
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Returns all attributes.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set an attribute.
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function setAttribute(string $name, string $value): static
    {
        if ($name === 'q') {
            $this->quality = (float) $value;
        } else {
            $this->attributes[$name] = $value;
        }

        return $this;
    }
}
