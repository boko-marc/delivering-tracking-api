<?php

namespace Module\Shared\Exceptions;

use Module\Shared\Contracts\ErrorContract;
use RuntimeException;
use Throwable;

class ApiException extends RuntimeException
{
    protected int $status;

    protected string $errorCode;

    protected ?array $data;

    public function __construct(
        int $status,
        ErrorContract $error,
        ?array $data = null,
        ?string $message = null,
        ?Throwable $previous = null
    ) {
        $this->status = $status;
        $this->errorCode = $error->code();
        $this->data = $data;

        parent::__construct(
            $message ?? $error->translationKey(),
            0,
            $previous
        );
    }

    public static function notFound(
        ErrorContract $error,
        ?array $data = null,
        ?string $message = null
    ): self {
        return new self(404, $error, $data, $message);
    }

    public static function forbidden(
        ErrorContract $error,
        ?array $data = null,
        ?string $message = null
    ): self {
        return new self(403, $error, $data, $message);
    }

    public static function unauthorized(
        ErrorContract $error,
        ?array $data = null,
        ?string $message = null
    ): self {
        return new self(401, $error, $data, $message);
    }

    public static function badRequest(
        ErrorContract $error,
        ?array $data = null,
        ?string $message = null
    ): self {
        return new self(400, $error, $data, $message);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getData(): ?array
    {
        return $this->data;
    }
}
