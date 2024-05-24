<?php

namespace Henrik\Http\Message;

final class MessageBodyResource implements MessageBodyInterface
{
    /**
     * @var resource
     */
    private $body;

    /**
     * @var bool
     */
    private bool $seekable;

    /**
     * @param resource $body
     */
    public function __construct($body)
    {
        $this->body = $body;

        $metadata       = stream_get_meta_data($body);
        $this->seekable = $metadata['seekable'];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return (string) stream_get_contents($this->body);
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $length): bool|string
    {
        return stream_get_contents($this->body, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): null|bool|int
    {
        if ($this->seekable) {
            $offset = ftell($this->body);
            fseek($this->body, 0, SEEK_END);

            $size = ftell($this->body);
            fseek($this->body, (int) $offset, SEEK_SET);

            return $size;
        }

        return null;
    }
}