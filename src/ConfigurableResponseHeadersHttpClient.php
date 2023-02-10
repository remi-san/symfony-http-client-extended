<?php

declare(strict_types=1);

namespace Symfony\Component\HttpClient\Extended;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Extended\Response\ProxyResponse;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

final class ConfigurableResponseHeadersHttpClient implements HttpClientInterface
{
    /**
     * @param array<string, string|null> $headers use null values to unset existing headers
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private readonly array $headers = [],
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * @param array<mixed> $options
     *
     * @throws ExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $this->logger->debug(
            'HTTP Request sent.',
            [
                'method'  => $method,
                'url'     => $url,
                'options' => $options,
            ]
        );

        return $this->enrichResponse(
            $this->httpClient->request($method, $url, $options)
        );
    }

    public function stream(ResponseInterface|iterable $responses, float $timeout = null): ResponseStreamInterface
    {
        // We do not enrich streams
        return $this->httpClient->stream($responses, $timeout);
    }

    /**
     * @param array<mixed> $options
     */
    public function withOptions(array $options): static
    {
        $clone             = clone $this;
        $clone->httpClient = $this->httpClient->withOptions($options);

        return $clone;
    }

    private function enrichResponse(ResponseInterface $response): ResponseInterface
    {
        return new ProxyResponse(
            innerResponse: $response,
            customHeaders: $this->headers
        );
    }

    public static function cacheExpiresIn(
        int $maxAge,
        HttpClientInterface $httpClient,
        ClockInterface $clock,
        LoggerInterface $logger = new NullLogger()
    ): self {
        $expireDate = (new \DateTimeImmutable())->setTimestamp($clock->now()->getTimestamp() + $maxAge);

        $headers = [
            'etag'          => 'always-same-etag',
            'cache-control' => 'public, max-age='.$maxAge,
            'age'           => '0',
            'expires'       => $expireDate->format(\DateTimeInterface::RFC1123),
            'pragma'        => null,
        ];

        return new self($httpClient, $headers, $logger);
    }
}
