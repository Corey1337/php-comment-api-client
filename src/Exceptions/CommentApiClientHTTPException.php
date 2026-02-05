<?php

declare(strict_types=1);

namespace Corey\PhpCommentApiClient\Exceptions;

final class CommentApiClientHTTPException extends AbstractCommentApiClientException
{
    public function __construct(
        private readonly int $httpStatusCode,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}
