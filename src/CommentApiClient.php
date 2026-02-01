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
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientBadRequestException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientForbiddenException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientMalformedRequestPayloadException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientMalformedResponsePayloadException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientMethodNotAllowedException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientServerErrorException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientTooManyRequestsException;
use Corey\PhpCommentApiClient\Exceptions\CommentApiClientUnhandledHttpResponseCodeException;
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
        $uri = new Uri(self::URI)->withPath('/comments');

        $data = $this->makeRequest(
            'GET',
            $uri->toString()
        );

        $commentDtos = [];
        foreach ($data as $item) {
            $commentDtos[] = CommentDto::fromArray($item);
        }

        return new GetCommentsResponseDto($commentDtos);
    }

    /** @throws AbstractCommentApiClientException */
    public function createComment(CreateCommentRequestDto $requestDto): CreateCommentResponseDto
    {
        $uri = new Uri(self::URI)->withPath('/comment');

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
            CommentDto::fromArray($data)
        );
    }

    /** @throws AbstractCommentApiClientException */
    public function updateCommentById(UpdateCommentByIdRequestDto $requestDto): UpdateCommentByIdResponseDto
    {
        $uri = new Uri(self::URI)->withPath('/comment/'.$requestDto->id);

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
            CommentDto::fromArray($data)
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
                if (400 === $statusCode) {
                    throw new CommentApiClientBadRequestException("Bad request");
                }
                if (403 === $statusCode) {
                    throw new CommentApiClientForbiddenException("Forbidden");
                }
                if (405 === $statusCode) {
                    throw new CommentApiClientMethodNotAllowedException("Method not allowed");
                }
                if (429 === $statusCode) {
                    throw new CommentApiClientTooManyRequestsException("Too many requests");
                }
                if ($statusCode >= 500 && $statusCode < 600) {
                    throw new CommentApiClientServerErrorException("Server error: $statusCode");
                }

                throw new CommentApiClientUnhandledHttpResponseCodeException("Unhandled http response code: $statusCode");
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
