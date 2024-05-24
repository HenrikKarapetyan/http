<?php

declare(strict_types=1);

namespace Henrik\Http;

use Henrik\Contracts\Http\ResponseInterface;
use Henrik\Contracts\Session\CookieInterface;
use Henrik\Http\Message\MessageBodyResource;
use Henrik\Http\Message\MessageBodyString;
use Henrik\Session\Cookie;
use InvalidArgumentException;
use RuntimeException;

/**
 * Represents an HTTP response to send back to the client. This class is immutable.
 *
 * @psalm-immutable
 */
class Response extends Message implements ResponseInterface
{
    /**
     * @var int
     */
    private int $statusCode = 200;

    /**
     * @var string
     */
    private string $reasonPhrase = 'OK';

    /**
     * @var CookieInterface[]
     */
    private array $cookies = [];

    /**
     * @param string                $content
     * @param int                   $status
     * @param array<string, string> $headers
     */
    public function __construct(mixed $content = '', int $status = 200, array $headers = [])
    {
        parent::__construct();
        $this->withContent($content);
        $this->withStatusCode($status);
        $this->withHeaders($headers);
    }

    /**
     * Parses a raw response string, including headers and body, and returns a Response object.
     *
     * @param string $response
     *
     * @throws RuntimeException
     *
     * @return self
     */
    public static function parse(string $response): self
    {
        $responseObject = new Response();

        if (preg_match('/^HTTP\/([0-9]\.[0-9]) ([0-9]{3}) .*\r\n/', $response, $matches) !== 1) {
            throw new RuntimeException('Could not parse response (error 1).');
        }

        [$line, $protocolVersion, $statusCode] = $matches;

        $responseObject = $responseObject->withProtocolVersion($protocolVersion);
        $responseObject = $responseObject->withStatusCode((int) $statusCode);

        $response = substr($response, strlen($line));

        while (true) {
            $pos = strpos($response, Message::CRLF);
            if ($pos === false) {
                throw new RuntimeException('Could not parse response (error 2).');
            }

            if ($pos === 0) {
                break;
            }

            $header = substr($response, 0, $pos);

            if (preg_match('/^(\S+):\s*(.*)$/', $header, $matches) !== 1) {
                throw new RuntimeException('Could not parse response (error 3).');
            }

            [$line, $name, $value] = $matches;
            $responseObject        = $responseObject->withAddedHeader($name, $value);

            if (strtolower($name) === 'set-cookie') {
                $responseObject = $responseObject->withCookie(Cookie::parse($value));
            }

            $response = substr($response, strlen($line) + 2);
        }

        $body = substr($response, 2);

        return $responseObject->withContent($body);
    }

    /**
     * Returns the status code of this response.
     *
     * @return int the status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns the reason phrase of this response.
     *
     * @return string
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Returns a copy of this response with a new status code.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param int         $statusCode   the status code
     * @param string|null $reasonPhrase an optional reason phrase, or null to use the default
     *
     * @throws InvalidArgumentException if the status code is not valid
     *
     * @return Response the updated response
     */
    public function withStatusCode(int $statusCode, ?string $reasonPhrase = null): Response
    {
        $that = clone $this;

        if ($statusCode < 100 || $statusCode > 999) {
            throw new InvalidArgumentException('Invalid  status code: ' . $statusCode);
        }

        if ($reasonPhrase === null) {
            $reasonPhrase = self::STATUS_CODES[$statusCode]
                ?? 'Unknown';
        }

        $that->statusCode   = $statusCode;
        $that->reasonPhrase = $reasonPhrase;

        return $that;
    }

    /**
     * Returns the cookies currently set on this response.
     *
     * @return CookieInterface[]
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Returns a copy of this response with the given cookie set.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param CookieInterface $cookie the cookie to set
     *
     * @return Response the updated response
     */
    public function withCookie(CookieInterface $cookie): Response
    {
        $that            = clone $this;
        $that->cookies[] = $cookie;

        return $that->withAddedHeader('Set-Cookie', (string) $cookie);
    }

    /**
     * Returns a copy of this response with all cookies removed.
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @return Response the updated response
     */
    public function withoutCookies(): Response
    {
        $that          = clone $this;
        $that->cookies = [];

        return $that->withoutHeader('Set-Cookie');
    }

    /**
     * Returns a copy of this response with a new content.
     *
     * This is a convenience method for setBody().
     *
     * This instance is immutable and unaffected by this method call.
     *
     * @param string|resource $content the response content
     *
     * @return Response the updated response
     */
    public function withContent($content): ResponseInterface
    {
        $body = '';
        if (is_resource($content)) {
            $body = new MessageBodyResource($content);
        }

        if (is_string($content)) {
            $body = new MessageBodyString($content);
        }

        return $this->withBody($body);
    }

    /**
     * Returns whether this response has an informational status code, 1xx.
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Returns whether this response has a successful status code, 2xx.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Returns whether this response has a redirection status code, 3xx.
     *
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Returns whether this response has a client error status code, 4xx.
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Returns whether this response has a server error status code, 5xx.
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Returns whether this response has the given status code.
     *
     * @param int $statusCode
     *
     * @return bool
     */
    public function isStatusCode(int $statusCode): bool
    {
        return $this->statusCode === $statusCode;
    }

    /**
     * Sends the response.
     *
     * This method will fail (return `false`) if the headers have been already sent.
     *
     * @psalm-suppress ImpureFunctionCall
     *
     * @return bool whether the response has been successfully sent
     */
    public function send(): bool
    {
        if (headers_sent()) {
            return false;
        }

        header($this->getStartLine());

        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                if (is_string($value)) {
                    header($name . ': ' . $value, false);
                }

            }
        }

        echo (string) $this->body;

        flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getStartLine(): string
    {
        return sprintf('HTTP/%s %d %s', $this->protocolVersion, $this->statusCode, $this->reasonPhrase);
    }
}