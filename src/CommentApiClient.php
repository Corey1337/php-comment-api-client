<?php

declare(strict_types=1);

namespace Corey\PhpCommentApiClient;

use Corey\PhpCommentApiClient\Dtos\CommentDto;
use Corey\PhpCommentApiClient\Dtos\Requests\CreateCommentRequestDto;
use Corey\PhpCommentApiClient\Dtos\Requests\UpdateCommentByIdRequestDto;
use Corey\PhpCommentApiClient\Dtos\Responses\CreateCommentResponseDto;
use Corey\PhpCommentApiClient\Dtos\Responses\GetCommentsResponseDto;
use Corey\PhpCommentApiClient\Dtos\Responses\UpdateCommentByIdResponseDto;
use Corey\PhpCommentApiClient\Exceptions\AbstractCommentApiClientException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientHTTPException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientMalformedRequestPayloadException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientMalformedResponsePayloadException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientUnknownException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Uri\Rfc3986\Uri;

class CommentApiClient implements CommentApiClientInterface
{
    public const string URI = 'http://example.com';

    public function __construct(
        private ?ClientInterface $client = null,
        private ?RequestFactoryInterface $requestFactory = null,
        private ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    /** @throws AbstractCommentApiClientException */
    public function getComments(): GetCommentsResponseDto
    {
        $uri = new Uri(static::URI)->withPath('/comments');

        $data = $this->makeRequest(
            'GET',
            $uri->toString()
        );

        $commentDtos = [];
        foreach ($data as $item) {
            $commentDtos[] = $this->buildCommentDto($item);
        }

        return new GetCommentsResponseDto($commentDtos);
    }

    /** @throws AbstractCommentApiClientException */
    public function createComment(CreateCommentRequestDto $requestDto): CreateCommentResponseDto
    {
        $uri = new Uri(static::URI)->withPath('/comment');

        $payload = [
            'name' => $requestDto->name,
            'text' => $requestDto->text,
        ];

        $data = $this->makeRequest(
            'POST',
            $uri->toString(),
            $payload,
        );

        return new CreateCommentResponseDto(
            $this->buildCommentDto($data)
        );
    }

    /** @throws AbstractCommentApiClientException */
    public function updateCommentById(UpdateCommentByIdRequestDto $requestDto): UpdateCommentByIdResponseDto
    {
        $uri = new Uri(static::URI)->withPath('/comment/'.$requestDto->id);

        $payload = [
            'name' => $requestDto->name,
            'text' => $requestDto->text,
        ];

        $data = $this->makeRequest(
            'PUT',
            $uri->toString(),
            $payload,
        );

        return new UpdateCommentByIdResponseDto(
            $this->buildCommentDto($data)
        );
    }

    protected function buildCommentDto(array $data): CommentDto
    {
        if (!isset($data['id'], $data['name'], $data['text'])) {
            throw new CommentApiClientMalformedResponsePayloadException();
        }

        return new CommentDto(
            id: (int) $data['id'],
            name: (string) $data['name'],
            text: (string) $data['text'],
        );
    }

    /** @throws AbstractCommentApiClientException */
    private function makeRequest(string $method, string $uri, ?array $payload = null): array
    {
        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json')
        ;

        if (null !== $payload) {
            $request = $request->withHeader('Content-Type', 'application/json');

            try {
                $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new CommentApiClientMalformedRequestPayloadException($exception->getMessage());
            }

            $request = $request->withBody($this->streamFactory->createStream($encodedPayload));
        }

        try {
            $response = $this->client->sendRequest($request);

            $statusCode = $response->getStatusCode();
            if (200 !== $statusCode && 201 !== $statusCode) {
                $reason = $response->getReasonPhrase();
                $message = "HTTP Error status code: {$statusCode} {$reason}";

                throw new CommentApiClientHTTPException(
                    httpStatusCode: $statusCode,
                    message: $message
                );
            }
        } catch (ClientExceptionInterface $exception) {
            throw new CommentApiClientUnknownException($exception->getMessage());
        }

        try {
            $data = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new CommentApiClientMalformedResponsePayloadException($exception->getMessage());
        }

        return $data;
    }
}
