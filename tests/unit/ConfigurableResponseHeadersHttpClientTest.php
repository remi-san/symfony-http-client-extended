<?php

declare(strict_types=1);

namespace Symfony\Component\HttpClient\Extended\Tests;

use Beste\Clock\FrozenClock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpClient\Extended\ConfigurableResponseHeadersHttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[Group('unit')]
#[Group('http')]
final class ConfigurableResponseHeadersHttpClientTest extends TestCase
{
    private MockHttpClient $proxiedHttpClient;

    /** @var array<string, string> */
    private const CONFIGURED_RESPONSE_HEADERS = [
        'myKey' => 'myVar',
    ];

    private HttpClientInterface $client;
    private ResponseInterface $streamedResponse;
    private const EXPIRES_IN = 10;
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->proxiedHttpClient = new MockHttpClient();
        $this->clock             = FrozenClock::fromUTC();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[DataProvider('responses')]
    #[Test]
    public function it_should_enrich_the_response_with_the_configured_headers(MockResponse $from_a_valid_response): void
    {
        $_this = $this; // #ignoreLine

        $_this->given_an_http_client_that_will_override_the_response_headers($from_a_valid_response);
        $_response = $_this->after_I_make_the_request();
        $_this->I_should_retrieve_a_response_with_the_preconfigured_headers($_response);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[Test]
    public function it_should_generate_an_error_if_the_original_response_is_in_error(): void
    {
        $_this = $this; // #ignoreLine

        $_this->given_an_http_client_that_will_override_the_response_headers($_this->from_a_response_in_error());
        $_response = $_this->after_I_make_the_request();
        $_this->I_should_get_an_error_when_accessing_the_response($_response);
    }

    #[Test]
    public function it_should_not_change_the_stream(): void
    {
        $_this           = $this; // #ignoreLine
        $_simpleResponse = $this->random_processed_response(); // #ignoreLine

        $_this->given_an_http_client_that_will_override_the_response_headers();
        $_response = $_this->when_streaming_a_response($_simpleResponse);
        $_this->I_should_retrieve_the_given_response($_response);
    }

    #[Test]
    public function it_copies_the_client_when_modifying_options(): void
    {
        $_this = $this; // #ignoreLine

        $_this->given_an_http_client_that_will_override_the_response_headers();
        $_newClient = $_this->when_modifying_the_client_options();
        $_this->I_should_have_an_updated_version_of_my_client($_newClient);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[DataProvider('responses')]
    #[Group('cache')]
    #[Test]
    public function it_changes_all_cache_control_headers_when_using_the_configured_cache_expires_inf_client(MockResponse $from_a_valid_response): void
    {
        $_this = $this; // #ignoreLine

        $_this->given_the_configured_http_client_that_will_override_cache_headers($from_a_valid_response);
        $_response = $_this->after_I_make_the_request();
        $_this->I_should_retrieve_a_response_with_the_preconfigured_cache_headers($_response);
    }

    // 1. Arrange

    private function given_an_http_client_that_will_override_the_response_headers(?ResponseInterface $response = null): void
    {
        $this->proxiedHttpClient->setResponseFactory(
            fn (string $method, string $url, array $options): ?ResponseInterface => $response
        );

        $this->client = new ConfigurableResponseHeadersHttpClient(
            $this->proxiedHttpClient,
            self::CONFIGURED_RESPONSE_HEADERS
        );
    }

    private function given_the_configured_http_client_that_will_override_cache_headers(?ResponseInterface $response = null): void
    {
        $this->proxiedHttpClient->setResponseFactory(
            fn (string $method, string $url, array $options): ?ResponseInterface => $response
        );

        $this->client = ConfigurableResponseHeadersHttpClient::cacheExpiresIn(
            self::EXPIRES_IN,
            $this->proxiedHttpClient,
            $this->clock
        );
    }

    private function from_a_response_in_error(): MockResponse
    {
        return new MockResponse('', ['http_code' => 500]);
    }

    public function random_processed_response(): MockResponse
    {
        return MockResponse::fromRequest('GET', '_random_request', [], new MockResponse());
    }

    // 2. Act

    /**
     * @throws TransportExceptionInterface
     */
    public function after_I_make_the_request(): ResponseInterface
    {
        return $this->client->request('GET', 'my_request');
    }

    private function when_streaming_a_response(ResponseInterface $response): ResponseStreamInterface
    {
        $this->streamedResponse = $response;

        return $this->client->stream($response);
    }

    private function when_modifying_the_client_options(): HttpClientInterface
    {
        return $this->client->withOptions([]);
    }

    // 3. Assert

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function I_should_retrieve_a_response_with_the_preconfigured_headers(ResponseInterface $response): void
    {
        $headers = $response->getHeaders(false);

        foreach (self::CONFIGURED_RESPONSE_HEADERS as $key => $value) {
            self::assertEquals($value, $headers[$key][0]);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function I_should_get_an_error_when_accessing_the_response(ResponseInterface $response): void
    {
        $this->expectException(ExceptionInterface::class);
        $response->getHeaders();
    }

    private function I_should_retrieve_the_given_response(ResponseStreamInterface $responseStream): void
    {
        self::assertEquals($this->streamedResponse, $responseStream->key());
    }

    private function I_should_have_an_updated_version_of_my_client(HttpClientInterface $newClient): void
    {
        self::assertInstanceOf(ConfigurableResponseHeadersHttpClient::class, $newClient);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function I_should_retrieve_a_response_with_the_preconfigured_cache_headers(ResponseInterface $response): void
    {
        $headers = $response->getHeaders(false);

        $expectedExpireDate = (new \DateTimeImmutable())
            ->setTimestamp($this->clock->now()->getTimestamp() + self::EXPIRES_IN)
            ->format(\DateTimeInterface::RFC1123);

        self::assertEquals('always-same-etag', $headers['etag'][0]);
        self::assertEquals('public, max-age='.self::EXPIRES_IN, $headers['cache-control'][0]);
        self::assertEquals(0, $headers['age'][0]);
        self::assertEquals($expectedExpireDate, $headers['expires'][0]);
        self::assertArrayNotHasKey('pragma', $headers);
    }

    // Data Providers

    /**
     * @return array<string, array<MockResponse>>
     */
    public static function responses(): array
    {
        return [
            'empty'  => [new MockResponse()],
            'header' => [new MockResponse('', ['response_headers' => ['myKey' => ['value_to_override']]])],
            'cache'  => [new MockResponse('', ['response_headers' => [
                'etag'          => ['original-etag'],
                'cache-control' => ['private, max-age=0'],
                'age'           => ['1000'],
                'expires'       => [(new \DateTimeImmutable())->format(\DateTimeInterface::RFC1123)],
                'pragma'        => ['no-cache'],
            ]])],
        ];
    }
}
