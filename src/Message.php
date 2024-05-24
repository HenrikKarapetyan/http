<?php

declare(strict_types=1);

namespace Henrik\Http;

use Henrik\Container\Exceptions\KeyAlreadyExistsException;
use Henrik\Http\Message\MessageBodyInterface;

/**
 * Base class for Request and Response. This class is immutable.
 *
 * @psalm-immutable
 */
abstract class Message
{
    public const CRLF = "\r\n";

    /**
     * @var string
     */
    protected string $protocolVersion = '1.0';

    /**
     * @var HeadersContainer $headers
     */
    protected HeadersContainer $headers;

    /**
     * @var MessageBodyInterface|null
     */
    protected ?MessageBodyInterface $body = null;

    public function __construct()
    {
        $this->headers = new HeadersContainer();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $message = $this->getHead();

        if ($this->body) {
            $message .= (string) $this->body;
        }

        return $message;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        if ($this->body) {
            $this->body = clone $this->body;
        }
    }

    /**
     * Returns the protocol version, such as '1.0'.
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * Returns a copy of this message with a new protocol version.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $version the HTTP protocol version
     *
     * @return static the updated message
     */
    public function withProtocolVersion(string $version): Message
    {
        $that                  = clone $this;
        $that->protocolVersion = $version;

        return $that;
    }

    /**
     * Gets all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        $headers = [];
        /** @var list<string> $values */
        foreach ($this->headers->getAll() as $name => $values) {
            $name           = implode('-', array_map('ucfirst', explode('-', strtolower($name))));
            $headers[$name] = $values;
        }

        return $headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        $name = strtolower($name);

        return $this->headers->has($name);
    }

    /**
     * Retrieves a header by the given case-insensitive name as a string.
     *
     * This method returns all the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * @param string $name
     *
     * @return string
     */
    public function getHeader(string $name): string
    {
        $name = strtolower($name);

        return isset($this->headers[$name]) ? implode(', ', $this->headers[$name]) : '';
    }

    /**
     * Returns the value of the first header by the given case-insensitive name, or null if no such header is present.
     *
     * @param string $name
     *
     * @return string|bool|null
     */
    public function getFirstHeader(string $name): null|bool|string
    {
        $name = strtolower($name);

        return isset($this->headers[$name]) ? reset($this->headers[$name]) : null;
    }

    /**
     * Returns the value of the last header by the given case-insensitive name, or null if no such header is present.
     *
     * @param string $name
     *
     * @return string|false|null
     */
    public function getLastHeader(string $name): null|false|string
    {
        $name = strtolower($name);

        return isset($this->headers[$name]) ? end($this->headers[$name]) : null;
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param string $name
     *
     * @return array<int, string>
     */
    public function getHeaderAsArray(string $name): array
    {
        $name = strtolower($name);

        return $this->headers[$name] ?? [];
    }

    /**
     * Returns a copy of this message with a new header.
     *
     * This replaces any existing values of any headers with the same
     * case-insensitive name in the original message.
     *
     * The header value MUST be a string or an array of strings.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string          $name  the header name
     * @param string|string[] $value the header values(s)
     *
     * @throws KeyAlreadyExistsException
     *
     * @return static the updated message
     */
    public function withHeader(string $name, array|string $value): Message
    {
        $that = clone $this;

        $name = strtolower($name);
        $that->headers->set($name, is_array($value) ? array_values($value) : [$value]);

        return $that;
    }

    /**
     * Returns a copy of this message with new headers.
     *
     * This replaces any headers that were set on the original message.
     *
     * The array keys MUST be strings. The array values MUST be strings or arrays of strings.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param array<string, string|string[]> $headers the header names & values
     *
     * @throws KeyAlreadyExistsException
     *
     * @return static the updated message
     */
    public function withHeaders(array $headers): Message
    {
        $that = $this;

        foreach ($headers as $name => $value) {
            $that = $that->withHeader($name, $value);
        }

        return $that;
    }

    /**
     * Returns a copy of this message with additional header values.
     *
     * The value is added to any existing values associated with the given header name.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string          $name  the header name
     * @param string|string[] $value the header value(s)
     *
     * @return static the updated message
     */
    public function withAddedHeader(string $name, array|string $value): Message
    {
        $that = clone $this;

        $name = strtolower($name);
        $this->headers->set($name, $value);

        return $that;
    }

    /**
     * Returns a copy of this message with additional headers.
     *
     * Each array key MUST be a string representing the case-insensitive name
     * of a header. Each value MUST be either a string or an array of strings.
     * For each value, the value is appended to any existing header of the same
     * name, or, if a header does not already exist by the given name, then the
     * header is added.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param array<string, string|string[]> $headers
     *
     * @return static the updated message
     */
    public function withAddedHeaders(array $headers): Message
    {
        $that = $this;

        foreach ($headers as $name => $value) {
            $that = $that->withAddedHeader($name, $value);
        }

        return $that;
    }

    /**
     * Returns a copy of this message without a specific header.
     *
     * The header name is matched case-insensitively
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string $name the header name
     *
     * @return static the updated message
     */
    public function withoutHeader(string $name): Message
    {
        $that = clone $this;

        $name = strtolower($name);
        $this->headers->delete($name);

        return $that;
    }

    /**
     * @return string
     */
    public function getHead(): string
    {
        $result = $this->getStartLine() . Message::CRLF;

        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $result .= $name . ': ' . $value . Message::CRLF;
            }
        }

        return $result . Message::CRLF;
    }

    /**
     * Returns the body of the message.
     *
     * @return MessageBodyInterface|null the body, or null if not set
     */
    public function getBody(): ?MessageBodyInterface
    {
        return $this->body;
    }

    /**
     * Returns a copy of this message with a new body.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param ?MessageBodyInterface $body
     *
     * @throws KeyAlreadyExistsException
     *
     * @return static the updated message
     */
    public function withBody(?MessageBodyInterface $body): Message
    {
        $that = clone $this;

        $that->body = $body;

        $that = $that->withoutHeader('Content-Length');
        $that = $that->withoutHeader('Transfer-Encoding');

        if ($body) {
            $size = $body->getSize();

            $that = $that->withHeader('Content-Length', (string) $size);
            $that = $that->withoutHeader('Transfer-Encoding');

            if ($size === null) {
                $that = $that->withHeader('Transfer-Encoding', 'chunked');
                $that = $that->withoutHeader('Content-Length');
            }
        }

        return $that;
    }

    /**
     * Returns the reported Content-Length of this Message.
     *
     * If the Content-Length header is absent or invalid, this method returns zero.
     *
     * @return int
     */
    public function getContentLength(): int
    {
        $contentLength = $this->getHeader('Content-Length');

        if (preg_match('/^[0-9]+$/', $contentLength) === 1) {
            return (int) $contentLength;
        }

        return 0;
    }

    /**
     * Returns whether this message has the given Content-Type.
     *
     * The given Content-Type must consist of the type and subtype, without parameters.
     * The comparison is case-insensitive, as per RFC 1521.
     *
     * @param string $contentType the Content-Type to check, such as `text/html`
     *
     * @return bool
     */
    public function isContentType(string $contentType): bool
    {
        $thisContentType = $this->getHeader('Content-Type');

        $pos = strpos($thisContentType, ';');

        if ($pos !== false) {
            $thisContentType = substr($thisContentType, 0, $pos);
        }

        return strtolower($contentType) === strtolower($thisContentType);
    }

    /**
     * Returns the start line of the Request or Response.
     *
     * @return string
     */
    abstract public function getStartLine(): string;
}