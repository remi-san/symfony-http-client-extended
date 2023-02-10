<?php

declare(strict_types=1);

namespace Symfony\Component\HttpClient\Extended\Tests\Helper;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class HeaderAssertHelper
{
    /**
     * @param non-empty-string $headerName
     */
    public function __construct(
        private ResponseInterface $response,
        private string $headerName
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function should_be(string $value): void
    {
        $responseHeaders = $this->response->getHeaders(false);
        TestCase::assertArrayHasKey($this->headerName, $responseHeaders);
        TestCase::assertEquals($value, $responseHeaders[$this->headerName][0]);

        $this->assertInInfoHeader(ProxyResponseAssertHelper::getInfoHeaders($this->response), $value);

        /** @var array<string> $headersFromInfo */
        $headersFromInfo = ProxyResponseAssertHelper::getInfo($this->response)[ProxyResponseAssertHelper::RESPONSE_HEADERS];
        $this->assertInInfoHeader($headersFromInfo, $value);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function should_be_inaccessible(): void
    {
        $responseHeaders = $this->response->getHeaders(false);
        TestCase::assertArrayNotHasKey($this->headerName, $responseHeaders);

        $this->assertNotInInfoHeader(ProxyResponseAssertHelper::getInfoHeaders($this->response));

        /** @var array<string> $headersFromInfo */
        $headersFromInfo = ProxyResponseAssertHelper::getInfo($this->response)[ProxyResponseAssertHelper::RESPONSE_HEADERS];
        $this->assertNotInInfoHeader($headersFromInfo);
    }

    /**
     * @param array<string> $responseHeadersFromInfo
     */
    private function assertInInfoHeader(array $responseHeadersFromInfo, string $value): void
    {
        foreach ($responseHeadersFromInfo as $rawHeader) {
            if (str_starts_with($rawHeader, $this->headerName)) {
                $infoHeaderValue = trim(substr($rawHeader, \strlen($this->headerName) + 2));
                TestCase::assertEquals($value, $infoHeaderValue);

                return;
            }
        }

        throw new ExpectationFailedException('Header does not exist');
    }

    /**
     * @param array<string> $responseHeadersFromInfo
     */
    public function assertNotInInfoHeader(array $responseHeadersFromInfo): void
    {
        foreach ($responseHeadersFromInfo as $rawHeader) {
            TestCase::assertStringStartsNotWith($this->headerName, $rawHeader);
        }
    }
}
