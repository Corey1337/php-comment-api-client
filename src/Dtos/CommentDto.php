<?php

declare(strict_types=1);

namespace Corey\PhpCommentApiClient\Dtos;

use Corey\PhpCommentApiClient\Exceptions\CommentApiClientMalformedResponsePayloadException;

final readonly class CommentDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $text,
    ) {}

    public static function fromArray(array $data): self
    {
        if (!isset($data['id'], $data['name'], $data['text'])) {
            throw new CommentApiClientMalformedResponsePayloadException();
        }

        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            text: (string) $data['text'],
        );
    }
}
