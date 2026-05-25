<?php

namespace Module\Shared\Enums;

use Module\Shared\Contracts\ErrorContract;

enum ErrorCode: string implements ErrorContract
{
    case INTERNAL_SERVER_ERROR = 'system.internal_server_error';
    case VALIDATION_ERROR = 'validation.error';
    case NOT_FOUND = 'not_found';
    case UNAUTHORIZED = 'auth.unauthenticated';
    case FORBIDDEN = 'auth.forbidden';
    case ROUTE_NOT_FOUND = 'route.not_found';
    case METHOD_NOT_ALLOWED = 'method.not_allowed';

    public function translationKey(): string
    {
        return match ($this) {
            self::INTERNAL_SERVER_ERROR => 'errors.internal_server_error',
            self::VALIDATION_ERROR => 'errors.validation_failed',
            self::NOT_FOUND => 'errors.not_found',
            self::UNAUTHORIZED => 'errors.unauthenticated',
            self::FORBIDDEN => 'errors.forbidden',
            self::ROUTE_NOT_FOUND => 'errors.route_not_found',
            self::METHOD_NOT_ALLOWED => 'errors.method_not_allowed',
        };
    }

    public function code(): string
    {
        return $this->value;
    }
}
