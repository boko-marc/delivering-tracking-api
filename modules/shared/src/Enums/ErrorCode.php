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
            self::INTERNAL_SERVER_ERROR => 'shared::errors.internal_server_error',
            self::VALIDATION_ERROR => 'shared::errors.validation_failed',
            self::NOT_FOUND => 'shared::errors.not_found',
            self::UNAUTHORIZED => 'shared::errors.unauthenticated',
            self::FORBIDDEN => 'shared::errors.forbidden',
            self::ROUTE_NOT_FOUND => 'shared::errors.route_not_found',
            self::METHOD_NOT_ALLOWED => 'errors.method_not_allowed',
        };
    }

    public function code(): string
    {
        return $this->value;
    }
}
