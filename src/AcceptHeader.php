<?php

namespace Henrik\Http;

/**
 * Represents an Accept-* header.
 *
 * An accept header is compound with a list of items,
 * sorted by descending quality.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 */
class AcceptHeader
{
    /**
     * @var AcceptHeaderItem[]
     */
    private array $items = [];

    private bool $sorted = true;

    /**
     * @param AcceptHeaderItem[] $items
     */
    public function __construct(array $items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Returns header value's string representation.
     */
    public function __toString(): string
    {
        return implode(',', $this->items);
    }

    /**
     * Builds an AcceptHeader instance from a string.
     *
     * @param ?string $headerValue
     */
    public static function fromString(?string $headerValue): self
    {
        $parts = HeaderUtils::split($headerValue ?? '', ',;=');

        return new self(array_map(function ($subParts) {
            static $index = 0;
            $part         = array_shift($subParts);
            $attributes   = HeaderUtils::combine($subParts);

            $item = new AcceptHeaderItem($part[0], $attributes);
            $item->setIndex($index++);

            return $item;
        }, $parts));
    }

    /**
     * Tests if header has given value.
     *
     * @param string $value
     */
    public function has(string $value): bool
    {
        return isset($this->items[$value]);
    }

    /**
     * Returns given value's item, if exists.
     *
     * @param string $value
     */
    public function get(string $value): ?AcceptHeaderItem
    {
        return $this->items[$value] ?? $this->items[explode('/', $value)[0] . '/*'] ?? $this->items['*/*'] ?? $this->items['*'] ?? null;
    }

    /**
     * Adds an item.
     *
     * @param AcceptHeaderItem $item
     *
     * @return $this
     */
    public function add(AcceptHeaderItem $item): static
    {
        $this->items[$item->getValue()] = $item;
        $this->sorted                   = false;

        return $this;
    }

    /**
     * Returns all items.
     *
     * @return AcceptHeaderItem[]
     */
    public function all(): array
    {
        $this->sort();

        return $this->items;
    }

    /**
     * Filters items on their value using given regex.
     *
     * @param string $pattern
     */
    public function filter(string $pattern): self
    {
        return new self(array_filter($this->items, fn (AcceptHeaderItem $item) => preg_match($pattern, $item->getValue())));
    }

    /**
     * Returns first item.
     */
    public function first(): ?AcceptHeaderItem
    {
        $this->sort();

        return $this->items ? reset($this->items) : null;
    }

    /**
     * Sorts items by descending quality.
     */
    private function sort(): void
    {
        if (!$this->sorted) {
            uasort($this->items, function (AcceptHeaderItem $a, AcceptHeaderItem $b) {
                $qA = $a->getQuality();
                $qB = $b->getQuality();

                if ($qA === $qB) {
                    return $a->getIndex() > $b->getIndex() ? 1 : -1;
                }

                return $qA > $qB ? -1 : 1;
            });

            $this->sorted = true;
        }
    }
}
