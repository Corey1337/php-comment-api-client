<?php

declare(strict_types=1);

namespace Corey\PhpCommentApiClient\Dtos\Responses;

use Corey\PhpCommentApiClient\Dtos\CommentDto;

final readonly class GetCommentsResponseDto
{
    /** @param iterable<CommentDto> $comments */
    public function __construct(
        public array $comments,
    ) {}
}
