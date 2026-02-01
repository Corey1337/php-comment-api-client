<?php

declare(strict_types=1);

namespace Corey\PhpCommentApiClient;

use Corey\PhpCommentApiClient\Dtos\Requests\CreateCommentRequestDto;
use Corey\PhpCommentApiClient\Dtos\Requests\UpdateCommentByIdRequestDto;
use Corey\PhpCommentApiClient\Dtos\Responses\CreateCommentResponseDto;
use Corey\PhpCommentApiClient\Dtos\Responses\GetCommentsResponseDto;
use Corey\PhpCommentApiClient\Dtos\Responses\UpdateCommentByIdResponseDto;

interface CommentApiClientInterface
{
    public function getComments(): GetCommentsResponseDto;

    public function createComment(CreateCommentRequestDto $requestDto): CreateCommentResponseDto;

    public function updateCommentById(UpdateCommentByIdRequestDto $requestDto): UpdateCommentByIdResponseDto;
}
