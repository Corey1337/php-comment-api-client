<?php

declare(strict_types=1);

namespace Corey\PhpCommentApiClient\Dtos;

final readonly class CommentDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $text,
    ) {}
}
