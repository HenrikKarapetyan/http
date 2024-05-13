<?php

declare(strict_types=1);

namespace henrik\http;

use Fig\Http\Message\StatusCodeInterface;
use henrik\http\Trait\ResponseTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response implements ResponseInterface, StatusCodeInterface
{
    use ResponseTrait;

    /**
     * @param int                         $statusCode
     * @param string[]                    $headers
     * @param string|StreamInterface|null $body
     * @param string                      $protocol
     * @param string                      $reasonPhrase
     */
    public function __construct(
        int $statusCode = self::STATUS_OK,
        array $headers = [],
        null|StreamInterface|string $body = null,
        string $protocol = '1.1',
        string $reasonPhrase = ''
    ) {
        $this->init($statusCode, $reasonPhrase, $headers, $body, $protocol);
    }
}