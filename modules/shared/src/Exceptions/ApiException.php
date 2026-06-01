<?php

declare(strict_types=1);

namespace Module\Shared\Exceptions;

use Module\Shared\Contracts\ErrorContract;
use RuntimeException;
use Throwable;

class ApiException extends RuntimeException
{
    protected string $errorCode;

    /**
     * @param  array<array-key, mixed>|null  $data
     */
    public function __construct(
        protected int $status,
        ErrorContract $error,
        protected ?array $data = null,
        ?string $message = null,
        ?Throwable $previous = null
    ) {
        $this->errorCode = $error->code();

        parent::__construct(
            $message ?? $error->translationKey(),
            0,
            $previous
        );
    }

    /**
     * @param  array<array-key, mixed>|null  $data
     */
    public static function notFound(ErrorContract $error, ?array $data = null, ?string $message = null): self
    {
        return new self(404, $error, $data, $message);
    }

    /**
     * @param  array<array-key, mixed>|null  $data
     */
    public static function forbidden(ErrorContract $error, ?array $data = null, ?string $message = null): self
    {
        return new self(403, $error, $data, $message);
    }

    /**
     * @param  array<array-key, mixed>|null  $data
     */
    public static function unauthorized(ErrorContract $error, ?array $data = null, ?string $message = null): self
    {
        return new self(401, $error, $data, $message);
    }

    /**
     * @param  array<array-key, mixed>|null  $data
     */
    public static function badRequest(ErrorContract $error, ?array $data = null, ?string $message = null): self
    {
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

    /**
     * @return array<array-key, mixed>|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }
}
