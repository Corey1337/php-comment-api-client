<?php

namespace Corey\PhpCommentApiClient\Tests;

use Corey\PhpCommentApiClient\CommentApiClient;
use Corey\PhpCommentApiClient\Dtos\Requests\CreateCommentRequestDto;
use Corey\PhpCommentApiClient\Dtos\Requests\UpdateCommentByIdRequestDto;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientHTTPException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientMalformedResponsePayloadException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

/**
 * @internal
 */
#[CoversClass(CommentApiClient::class)]
class CommentApiClientTest extends TestCase
{
    private ClientInterface&MockObject $mockHttpClient;
    private CommentApiClient $apiClient;

    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(ClientInterface::class);

        $this->apiClient = new CommentApiClient(
            $this->mockHttpClient,
            new Psr17Factory(),
            new Psr17Factory(),
        );
    }

    #[DataProvider('provideGetCommentsSuccessCases')]
    public function testGetCommentsSuccess(int $exceptedCommentsCount, string $responsePayload): void
    {
        $mockResponse = new Response(200, ['Content-Type' => 'application/json'], $responsePayload);
        $this->mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse)
        ;

        $result = $this->apiClient->getComments();

        $this->assertCount($exceptedCommentsCount, $result->comments);
    }

    public static function provideGetCommentsSuccessCases(): \Generator
    {
        yield 'empty comments list' => [0, json_encode([])];

        yield 'list with one comment' => [1, json_encode([
            ['id' => 2, 'name' => 'Corey', 'text' => 'OMG!'],
        ])];

        yield 'list with multiply comments' => [3, json_encode([
            ['id' => 1, 'name' => 'Vitalik', 'text' => 'NICE'],
            ['id' => 2, 'name' => 'Maksim', 'text' => 'COOL'],
            ['id' => 3, 'name' => 'Fenix', 'text' => 'GOOD'],
        ])];
    }

    public function testCreateCommentSuccess(): void
    {
        $requestDto = new CreateCommentRequestDto('Virtal', 'My new comment');

        $mockResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 123,
            'name' => $requestDto->name,
            'text' => $requestDto->text,
        ]));
        $this->mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse)
        ;

        $result = $this->apiClient->createComment($requestDto);

        $this->assertEquals($requestDto->name, $result->comment->name);
        $this->assertEquals($requestDto->text, $result->comment->text);
    }

    public function testUpdateCommentSuccess(): void
    {
        $requestDto = new UpdateCommentByIdRequestDto(
            id: 12,
            name: 'Virtal',
            text: 'Its cool! upd: not anymore'
        );

        $mockResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => $requestDto->id,
            'name' => $requestDto->name,
            'text' => $requestDto->text,
        ]));
        $this->mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse)
        ;

        $result = $this->apiClient->updateCommentById($requestDto);

        $this->assertEquals($requestDto->id, $result->comment->id);
        $this->assertEquals($requestDto->name, $result->comment->name);
        $this->assertEquals($requestDto->text, $result->comment->text);
    }

    #[DataProvider('provideHttpErrorCases')]
    public function testHttpErrorsThrowSpecificExceptions(int $statusCode): void
    {
        $mockResponse = new Response($statusCode, ['Content-Type' => 'application/json'], '{}');

        $this->mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse)
        ;

        try {
            $this->apiClient->getComments();
            $this->fail('An exception should have been thrown.');
        } catch (CommentApiClientHTTPException $exception) {
            $this->assertEquals($statusCode, $exception->getHttpStatusCode());
        }
    }

    public static function provideHttpErrorCases(): \Generator
    {
        yield '400 Bad Request' => [400];

        yield '403 Forbidden' => [403];

        yield '405 Method Not Allowed' => [405];

        yield '429 Too Many Requests' => [429];

        yield '500 Server Error' => [500];

        yield '503 Service Unavailable' => [503];

        yield '418 Response Code' => [418];
    }

    public function testMalformedJsonResponseThrowsException(): void
    {
        $mockResponse = new Response(200, [], 'invalid-json');

        $this->mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse)
        ;

        $this->expectException(CommentApiClientMalformedResponsePayloadException::class);

        $this->apiClient->getComments();
    }

    public function testMissingDtoFieldsThrowException(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            ['id' => 1, 'name' => 'Fenix'],
        ]));

        $this->mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse)
        ;

        $this->expectException(CommentApiClientMalformedResponsePayloadException::class);

        $this->apiClient->getComments();
    }
}
