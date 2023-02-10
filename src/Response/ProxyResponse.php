<?php

declare(strict_types=1);

namespace Symfony\Component\HttpClient\Extended\Response;

use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class ProxyResponse implements ResponseInterface
{
    /**
     * @param array<string|null> $customHeaders You can set a header value to null if you want to remove it from the headers
     */
    public function __construct(
        private ResponseInterface $innerResponse,
        private ?int $customStatusCode = null,
        private array $customHeaders = [],
        private ?string $customBody = null
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->customStatusCode ?? $this->innerResponse->getStatusCode();
    }

    /**
     * @return array<array<string>>
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getHeaders(bool $throw = true): array
    {
        $headers   = $this->innerResponse->getHeaders($throw);

        $unsetList = array_keys(
            array_filter(
                $this->customHeaders,
                static fn (?string $value): bool => $value === null
            )
        );

        $headers = array_reduce(
            array_keys($headers),
            static function (array $filteredHeaders, string $headerName) use ($headers, $unsetList): array {
                if (!\in_array($headerName, $unsetList, true)) {
                    $filteredHeaders[$headerName] = $headers[$headerName];
                }

                return $filteredHeaders;
            },
            []
        );

        return array_merge(
            $headers,
            array_map(
                static fn (string $value): array => [$value],
                array_filter(
                    $this->customHeaders,
                    static fn (?string $value): bool => $value !== null
                )
            )
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getContent(bool $throw = true): string
    {
        return $this->customBody ?? $this->innerResponse->getContent($throw);
    }

    /**
     * @return array<mixed>
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function toArray(bool $throw = true): array
    {
        if ($this->customBody === null) {
            return $this->innerResponse->toArray($throw);
        }

        try {
            /** @var array<mixed> $array */
            $array = json_decode($this->customBody, true, 512, \JSON_THROW_ON_ERROR);

            return $array;
        } catch (\JsonException $e) {
            throw new JsonException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function cancel(): void
    {
        $this->innerResponse->cancel();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    public function getInfo(string $type = null): mixed
    {
        /** @var mixed|array<string, mixed> $default */
        $default = $this->innerResponse->getInfo($type);

        return match ($type) {
            'http_code'        => $this->customStatusCode ?? $default,
            'response_headers' => $this->customInfoHeaders(),
            'debug'            => $default."\r\n".'* Modified response after reception: '.$this->getModificationsListAsString().\PHP_EOL,
            null               => array_reduce(
                \is_array($default) ? array_keys($default) : [],
                function (array $info, string $key): array {
                    $info[$key] = $this->getInfo($key);

                    return $info;
                },
                []
            ),
            default            => $default,
        };
    }

    /**
     * @return array<string>
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function customInfoHeaders(): array
    {
        $innerHeaders = $this->getHeaders(false);

        return array_reduce(
            array_keys($innerHeaders),
            static function (array $headers, string $headerName) use ($innerHeaders): array {
                foreach ($innerHeaders[$headerName] as $headerValues) {
                    $headers[] = $headerName.': '.$headerValues;
                }

                return $headers;
            },
            []
        );
    }

    /**
     * @throws \JsonException
     */
    private function getModificationsListAsString(): string
    {
        return json_encode([
            'status-code'   => $this->customStatusCode,
            'headers'       => $this->customHeaders,
            'content'       => $this->customBody,
        ], \JSON_THROW_ON_ERROR);
    }
}
