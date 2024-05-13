<?php

declare(strict_types=1);

namespace henrik\http\Trait;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

trait ResponseTrait
{
    use MessageTrait;

    /**
     * Map of standard HTTP status code and reason phrases.
     *
     * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @var array<int, string>
     */
    private const PHRASES = [
        // Informational 1xx
        self::STATUS_CONTINUE            => 'Continue',
        self::STATUS_SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::STATUS_PROCESSING          => 'Processing',
        self::STATUS_EARLY_HINTS         => 'Early Hints',
        // Successful 2xx
        self::STATUS_OK                            => 'OK',
        self::STATUS_CREATED                       => 'Created',
        self::STATUS_ACCEPTED                      => 'Accepted',
        self::STATUS_NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
        self::STATUS_NO_CONTENT                    => 'No Content',
        self::STATUS_RESET_CONTENT                 => 'Reset Content',
        self::STATUS_PARTIAL_CONTENT               => 'Partial Content',
        self::STATUS_MULTI_STATUS                  => 'Multi-Status',
        self::STATUS_ALREADY_REPORTED              => 'Already Reported',
        self::STATUS_IM_USED                       => 'IM Used',
        // Redirection 3xx
        self::STATUS_MULTIPLE_CHOICES   => 'Multiple Choices',
        self::STATUS_MOVED_PERMANENTLY  => 'Moved Permanently',
        self::STATUS_FOUND              => 'Found',
        self::STATUS_SEE_OTHER          => 'See Other',
        self::STATUS_NOT_MODIFIED       => 'Not Modified',
        self::STATUS_USE_PROXY          => 'Use Proxy',
        self::STATUS_TEMPORARY_REDIRECT => 'Temporary Redirect',
        self::STATUS_PERMANENT_REDIRECT => 'Permanent Redirect',
        // Client Errors 4xx
        self::STATUS_BAD_REQUEST                     => 'Bad Request',
        self::STATUS_UNAUTHORIZED                    => 'Unauthorized',
        self::STATUS_PAYMENT_REQUIRED                => 'Payment Required',
        self::STATUS_FORBIDDEN                       => 'Forbidden',
        self::STATUS_NOT_FOUND                       => 'Not Found',
        self::STATUS_METHOD_NOT_ALLOWED              => 'Method Not Allowed',
        self::STATUS_NOT_ACCEPTABLE                  => 'Not Acceptable',
        self::STATUS_PROXY_AUTHENTICATION_REQUIRED   => 'Proxy Authentication Required',
        self::STATUS_REQUEST_TIMEOUT                 => 'Request Timeout',
        self::STATUS_CONFLICT                        => 'Conflict',
        self::STATUS_GONE                            => 'Gone',
        self::STATUS_LENGTH_REQUIRED                 => 'Length Required',
        self::STATUS_PRECONDITION_FAILED             => 'Precondition Failed',
        self::STATUS_PAYLOAD_TOO_LARGE               => 'Payload Too Large',
        self::STATUS_URI_TOO_LONG                    => 'URI Too Long',
        self::STATUS_UNSUPPORTED_MEDIA_TYPE          => 'Unsupported Media Type',
        self::STATUS_RANGE_NOT_SATISFIABLE           => 'Range Not Satisfiable',
        self::STATUS_EXPECTATION_FAILED              => 'Expectation Failed',
        self::STATUS_IM_A_TEAPOT                     => 'I\'m a teapot',
        self::STATUS_MISDIRECTED_REQUEST             => 'Misdirected Request',
        self::STATUS_UNPROCESSABLE_ENTITY            => 'Unprocessable Entity',
        self::STATUS_LOCKED                          => 'Locked',
        self::STATUS_FAILED_DEPENDENCY               => 'Failed Dependency',
        self::STATUS_TOO_EARLY                       => 'Too Early',
        self::STATUS_UPGRADE_REQUIRED                => 'Upgrade Required',
        self::STATUS_PRECONDITION_REQUIRED           => 'Precondition Required',
        self::STATUS_TOO_MANY_REQUESTS               => 'Too Many Requests',
        self::STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
        self::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS   => 'Unavailable For Legal Reasons',
        // Server Errors 5xx
        self::STATUS_INTERNAL_SERVER_ERROR           => 'Internal Server Error',
        self::STATUS_NOT_IMPLEMENTED                 => 'Not Implemented',
        self::STATUS_BAD_GATEWAY                     => 'Bad Gateway',
        self::STATUS_SERVICE_UNAVAILABLE             => 'Service Unavailable',
        self::STATUS_GATEWAY_TIMEOUT                 => 'Gateway Timeout',
        self::STATUS_VERSION_NOT_SUPPORTED           => 'HTTP Version Not Supported',
        self::STATUS_VARIANT_ALSO_NEGOTIATES         => 'Variant Also Negotiates',
        self::STATUS_INSUFFICIENT_STORAGE            => 'Insufficient Storage',
        self::STATUS_LOOP_DETECTED                   => 'Loop Detected',
        self::STATUS_NOT_EXTENDED                    => 'Not Extended',
        self::STATUS_NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
    ];

    /**
     * @var int
     */
    private int $statusCode;

    /**
     * @var string
     */
    private string $reasonPhrase;

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @param int    $code         the 3-digit integer result code to set
     * @param string $reasonPhrase the reason phrase to use with the
     *                             provided status code; if none is provided, implementations MAY
     *                             use the defaults as suggested in the HTTP specification
     *
     * @return ResponseInterface
     *
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress TypeDoesNotContainType
     * @psalm-suppress RedundantCondition
     * @psalm-suppress NoValue
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $new = clone $this;
        $new->setStatus($code, $reasonPhrase);

        return $new;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @return string reason phrase; must return an empty string if none present
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * @param int                                  $statusCode
     * @param string                               $reasonPhrase
     * @param StreamInterface|string|resource|null $body
     * @param array                                $headers
     * @param string                               $protocol
     */
    private function init(
        int $statusCode = 200,
        string $reasonPhrase = '',
        array $headers = [],
        $body = null,
        string $protocol = '1.1'
    ): void {
        $this->setStatus($statusCode, $reasonPhrase);
        $this->registerStream($body);
        $this->registerHeaders($headers);
        $this->registerProtocolVersion($protocol);
    }

    /**
     * @param int    $statusCode
     * @param string $reasonPhrase
     *
     * @throws InvalidArgumentException for invalid status code arguments
     *
     * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     */
    private function setStatus(int $statusCode, string $reasonPhrase = ''): void
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(sprintf(
                'Response status code "%d" is not valid. It must be in 100..599 range.',
                $statusCode
            ));
        }

        $this->statusCode   = $statusCode;
        $this->reasonPhrase = $reasonPhrase ?: (self::PHRASES[$statusCode] ?? '');
    }
}