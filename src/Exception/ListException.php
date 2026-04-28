<?php

declare(strict_types=1);

namespace App\Exception;

final class ListException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        private readonly string $field = 'id',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getField(): string
    {
        return $this->field;
    }
}
