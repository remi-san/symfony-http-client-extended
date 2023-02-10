<?php

declare(strict_types=1);

namespace Symfony\Component\HttpClient\Extended\Tests\Helper;

use PhpProject\Fluent\LinkWordsFluentTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Extended\Response\ProxyResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class ProxyResponseAssertHelper
{
    use LinkWordsFluentTrait;

    public const STATUS_CODE      = 'http_code';
    public const RESPONSE_HEADERS = 'response_headers';
    public const DEBUG            = 'debug';

    public function __construct(
        private ResponseInterface $response
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function the_status_code_should_be(int $statusCode): void
    {
        TestCase::assertEquals($statusCode, $this->response->getStatusCode());
        TestCase::assertEquals($statusCode, self::getInfoStatusCode($this->response));
        TestCase::assertEquals($statusCode, self::getInfo($this->response)[self::STATUS_CODE]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function the_body_should_be(string $body): void
    {
        TestCase::assertEquals($body, $this->response->getContent(false));
    }

    /**
     * @param array<mixed> $body
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     */
    public function the_body_array_should_be(array $body): void
    {
        TestCase::assertEquals($body, $this->response->toArray(false));
    }

    /**
     * @param non-empty-string $headerName
     */
    public function the_value_of_header(string $headerName): HeaderAssertHelper
    {
        return new HeaderAssertHelper(
            $this->response,
            $headerName
        );
    }

    public function the_debug_info_should_display_the_response_has_been_modified(): void
    {
        $debugInfo = self::getDebugInfo($this->response);
        TestCase::assertStringContainsString('Modified response after reception:', $debugInfo);
    }

    public function it_should_cancel_the_proxied_response(): void
    {
        $this->response->cancel();
        TestCase::assertInstanceOf(ProxyResponse::class, $this->response);
    }

    /**
     * @return array<mixed>
     */
    public static function getInfo(ResponseInterface $response): array
    {
        /** @var array<mixed> $info */
        $info = $response->getInfo();

        return $info;
    }

    public static function getInfoStatusCode(ResponseInterface $response): int
    {
        /** @var int $infoStatusCode */
        $infoStatusCode = $response->getInfo(self::STATUS_CODE);

        return $infoStatusCode;
    }

    /**
     * @return array<string>
     */
    public static function getInfoHeaders(ResponseInterface $response): array
    {
        /** @var array<string> $infoHeaders */
        $infoHeaders = $response->getInfo(self::RESPONSE_HEADERS);

        return $infoHeaders;
    }

    public static function getDebugInfo(ResponseInterface $response): string
    {
        /** @var string $debugInfo */
        $debugInfo = $response->getInfo(self::DEBUG);

        return $debugInfo;
    }
}
