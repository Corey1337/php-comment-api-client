<?php

declare(strict_types=1);

namespace Corey\PhpCommentApiClient\Dtos\Responses;

use Corey\PhpCommentApiClient\Dtos\CommentDto;

final readonly class CreateCommentResponseDto
{
    public function __construct(
        public CommentDto $comment,
    ) {}
}
