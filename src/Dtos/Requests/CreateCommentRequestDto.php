<?php

declare(strict_types=1);

namespace Corey\PhpCommentApiClient\Dtos\Requests;

final readonly class CreateCommentRequestDto
{
    public function __construct(
        public string $name,
        public string $text,
    ) {}
}
