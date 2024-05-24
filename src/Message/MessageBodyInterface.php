<?php

namespace Henrik\Http\Message;

use Stringable;

interface MessageBodyInterface extends Stringable
{
    /**
     * @return string
     */
    public function __toString(): string;

    /**
     * @param int $length
     *
     * @return string|bool
     */
    public function read(int $length): bool|string;

    /**
     * Returns the size of the body if known.
     *
     * @return int|bool|null the size in bytes if known, or null if unknown
     */
    public function getSize(): null|bool|int;
}