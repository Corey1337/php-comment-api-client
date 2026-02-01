<?php

declare(strict_types=1);

namespace Corey\PhpCommentApiClient\Dtos\Requests;

final readonly class UpdateCommentByIdRequestDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $text,
    ) {}
}
