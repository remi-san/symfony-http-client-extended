<?php

declare(strict_types=1);

namespace Symfony\Component\HttpClient\Extended\Tests\Response;

use PhpProject\Fluent\LinkWordsFluentTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Extended\Response\ProxyResponse;
use Symfony\Component\HttpClient\Extended\Tests\Helper\ProxyResponseAssertHelper;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Group('unit')]
#[Group('http')]
final class ProxyResponseTest extends TestCase
{
    use LinkWordsFluentTrait;

    private MockResponse $proxiedResponse;

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function it_replaces_the_status_code_by_the_parametered_status_code(): void
    {
        $this->given_a_response_with_the_following_status_code(404);
        $_proxiedResponse = $this->when_I_proxy_the_response_with_the_following_parametered_status_code(200);
        $_proxiedResponse->then()->the_status_code_should_be(200);
        $_proxiedResponse->and()->the_debug_info_should_display_the_response_has_been_modified();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function it_replaces_the_response_body_by_the_parametered_response_body(): void
    {
        $this->given_a_response_with_the_following_body('My body');
        $_proxiedResponse = $this->when_I_proxy_the_response_with_the_following_parametered_body('My new body');
        $_proxiedResponse->then()->the_body_should_be('My new body');
        $_proxiedResponse->and()->the_debug_info_should_display_the_response_has_been_modified();
    }

    /**
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     */
    #[Test]
    public function it_replaces_the_response_array_by_the_parametered_response_array(): void
    {
        $this->given_a_response_with_the_following_body_as_array(['My' => 'body']);
        $_proxiedResponse = $this->when_I_proxy_the_response_with_the_following_parametered_body_as_array(['My' => 'new body']);
        $_proxiedResponse->then()->the_body_array_should_be(['My' => 'new body']);
        $_proxiedResponse->and()->the_debug_info_should_display_the_response_has_been_modified();
    }

    /**
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     */
    #[Test]
    public function it_returns_the_response_array_if_not_given_a_custom_response_array(): void
    {
        $this->given_a_response_with_the_following_body_as_array(['My' => 'body']);
        $_proxiedResponse = $this->when_I_proxy_the_response_with_the_following_parametered_body_as_array(null);
        $_proxiedResponse->then()->the_body_array_should_be(['My' => 'body']);
        $_proxiedResponse->and()->the_debug_info_should_display_the_response_has_been_modified();
    }

    /**
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     */
    #[Test]
    public function it_throws_an_exception_if_given_a_bad_json_response(): void
    {
        $this->given_a_response_with_the_following_body_as_array(['My' => 'body']);
        $this->should_fail_with_the_following_exception(JsonException::class);
        $_proxiedResponse = $this->when_I_proxy_the_response_with_the_following_parametered_body('{"My": "body"');
        $_proxiedResponse->then()->the_body_array_should_be(['My' => 'body']);
        $_proxiedResponse->and()->the_debug_info_should_display_the_response_has_been_modified();
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function it_cancels_the_proxied_response_when_cancelled(): void
    {
        $this->given_a_response_with_the_following_body('content');
        $_proxiedResponse = $this->when_I_proxy_the_response_with_the_following_parametered_body('new content');
        $_proxiedResponse->then()->it_should_cancel_the_proxied_response();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function it_replaces_or_adds_or_deletes_the_response_headers_according_to_the_parametered_response_headers(): void
    {
        $this->given_a_response_with_the_following_headers([
            'header1' => 'header1 value',
            'header2' => 'header2 value',
        ]);
        $_proxiedResponse = $this->when_I_proxy_the_response_with_the_following_parametered_headers([
            'header1' => 'header1 new value',
            'header2' => null,
            'header3' => 'header3 value',
        ]);
        $_proxiedResponse->then()->the_value_of_header('header1')->should_be('header1 new value');
        $_proxiedResponse->and()->the_value_of_header('header2')->should_be_inaccessible();
        $_proxiedResponse->and()->the_value_of_header('header3')->should_be('header3 value');

        $_proxiedResponse->and()->the_debug_info_should_display_the_response_has_been_modified();
    }

    // 1. Arrange

    private function given_a_response_with_the_following_status_code(int $statusCode): void
    {
        $this->response(
            new MockResponse(
                '[]',
                [
                    'status_code' => $statusCode,
                ]
            )
        );
    }

    private function given_a_response_with_the_following_body(string $body): void
    {
        $this->response(
            new MockResponse($body)
        );
    }

    /**
     * @param array<mixed> $bodyAsArray
     *
     * @throws \JsonException
     */
    private function given_a_response_with_the_following_body_as_array(array $bodyAsArray): void
    {
        $this->response(
            new MockResponse(json_encode($bodyAsArray, \JSON_THROW_ON_ERROR))
        );
    }

    /**
     * @param array<string, string> $headers
     */
    private function given_a_response_with_the_following_headers(array $headers): void
    {
        $this->response(new MockResponse(
            '[]',
            [
                ProxyResponseAssertHelper::RESPONSE_HEADERS => array_map(
                    static fn (string $value): array => [$value],
                    $headers
                ),
            ]
        )
        );
    }

    private function response(MockResponse $response): void
    {
        $this->proxiedResponse = MockResponse::fromRequest('GET', '', [], $response);
    }

    // 2. Act

    /**
     * @param array<string, string|null> $headers
     */
    private function when_I_proxy_the_response_with_the_following_parametered_headers(array $headers): ProxyResponseAssertHelper
    {
        return new ProxyResponseAssertHelper(new ProxyResponse(
            innerResponse: $this->proxiedResponse,
            customHeaders: $headers
        ));
    }

    private function when_I_proxy_the_response_with_the_following_parametered_status_code(int $statusCode): ProxyResponseAssertHelper
    {
        return new ProxyResponseAssertHelper(new ProxyResponse(
            innerResponse: $this->proxiedResponse,
            customStatusCode: $statusCode
        ));
    }

    private function when_I_proxy_the_response_with_the_following_parametered_body(string $body): ProxyResponseAssertHelper
    {
        return new ProxyResponseAssertHelper(new ProxyResponse(
            innerResponse: $this->proxiedResponse,
            customBody: $body
        ));
    }

    /**
     * @param array<mixed>|null $bodyAsArray
     *
     * @throws \JsonException
     */
    private function when_I_proxy_the_response_with_the_following_parametered_body_as_array(?array $bodyAsArray): ProxyResponseAssertHelper
    {
        return new ProxyResponseAssertHelper(new ProxyResponse(
            innerResponse: $this->proxiedResponse,
            customBody: ($bodyAsArray !== null) ? json_encode($bodyAsArray, \JSON_THROW_ON_ERROR) : null
        ));
    }

    // 3. Assert

    /**
     * @param class-string<\Throwable> $exception
     */
    private function should_fail_with_the_following_exception(string $exception): void
    {
        $this->expectException($exception);
    }
}
