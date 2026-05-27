# Exception & API Response Handling

## Overview

Errors are managed via the `ApiException` class and a global handler in `bootstrap/app.php`. All errors return JSON with a consistent schema. The API respects the `X-Locale` header for translations.

## Architecture

Two types of errors:

1. **Laravel Exceptions** — automatically caught and converted to JSON
2. **ApiException** — manually thrown for business logic errors

Each error implements `ErrorContract` (interface) via enums.

### ErrorContract Interface

```php
interface ErrorContract {
    public function code(): string;           // "NOT_FOUND", "CONFLICT", etc.
    public function translationKey(): string; // "errors.not_found"
}
```

### Shared ErrorCode

File: `modules/shared/src/Enums/ErrorCode.php`

```php
enum ErrorCode: string implements ErrorContract {
    case NOT_FOUND = 'NOT_FOUND';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case ROUTE_NOT_FOUND = 'ROUTE_NOT_FOUND';
    case METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    case INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    
    public function code(): string { return $this->value; }
    public function translationKey(): string { 
        return match($this) {
            self::NOT_FOUND => 'errors.not_found',
            // ...
        };
    }
}
```

## Throwing an ApiException

```php
use Module\Shared\Exceptions\ApiException;
use Module\Shared\Enums\ErrorCode;
use Modules\Delivery\Enums\DeliveryErrorCode;

// Standard error
throw ApiException::notFound(ErrorCode::NOT_FOUND);

// With contextual data
throw ApiException::notFound(
    ErrorCode::NOT_FOUND,
    data: ['resource_type' => 'package', 'id' => 123]
);

// Business error (module enum)
throw ApiException::badRequest(
    DeliveryErrorCode::INVALID_TRACKING_NUMBER,
    data: ['tracking_number' => 'ABC-123']
);

// Custom message (rare)
throw ApiException::notFound(
    ErrorCode::NOT_FOUND,
    message: __('custom.translation.key')
);
```

**Helpers**: `notFound()` (404), `badRequest()` (400), `unauthorized()` (401), `forbidden()` (403)

## Laravel Exceptions (Auto-Caught)

| Exception | Status | ErrorCode | Data |
|-----------|--------|-----------|------|
| `ModelNotFoundException` | 404 | `NOT_FOUND` | `{resource_type, resource_id}` |
| `ValidationException` | 422 | `VALIDATION_ERROR` | `{errors: {field: [rules]}}` |
| `NotFoundHttpException` | 404 | `ROUTE_NOT_FOUND` | `{url, method}` |
| `MethodNotAllowedHttpException` | 405 | `METHOD_NOT_ALLOWED` | `{method, allowed_methods}` |
| `AuthenticationException` | 401 | `UNAUTHORIZED` | `{message, url, method}` |
| `AccessDeniedHttpException` | 403 | `FORBIDDEN` | `{message, url, method}` |

**Examples**:

```php
// Auto → 404 NOT_FOUND
$package = Package::findOrFail($id);

// Auto → 422 VALIDATION_ERROR
$request->validate(['tracking_number' => 'required|unique']);

// Auto → 401 UNAUTHORIZED
abort(401);
```

## Creating a Business Error Enum

File: `modules/{module}/src/Enums/{Module}ErrorCode.php`

```php
<?php
namespace Modules\Delivery\Enums;

use Module\Shared\Contracts\ErrorContract;

enum DeliveryErrorCode: string implements ErrorContract {
    case PACKAGE_NOT_FOUND = 'DELIVERY_PACKAGE_NOT_FOUND';
    case INVALID_TRACKING_NUMBER = 'DELIVERY_INVALID_TRACKING';
    case STATUS_CONFLICT = 'DELIVERY_STATUS_CONFLICT';
    
    public function code(): string {
        return $this->value;
    }
    
    public function translationKey(): string {
        return match($this) {
            self::PACKAGE_NOT_FOUND => 'errors.package_not_found',
            self::INVALID_TRACKING_NUMBER => 'errors.invalid_tracking',
            self::STATUS_CONFLICT => 'errors.status_conflict',
        };
    }
}
```

File: `modules/delivery/lang/en/errors.php`

```php
return [
    'package_not_found' => 'Package not found',
    'invalid_tracking' => 'Invalid tracking number',
    'status_conflict' => 'The package status does not allow this action',
];
```

**Usage**:

```php
throw ApiException::notFound(
    DeliveryErrorCode::PACKAGE_NOT_FOUND,
    data: ['id' => $id]
);

throw ApiException::badRequest(
    DeliveryErrorCode::STATUS_CONFLICT,
    data: ['current' => 'in_transit', 'requested' => 'reschedule']
);
```

## Response Schemas

### Success (2xx)

```json
{
    "success": true,
    "data": { "id": 1, "name": "John" },
    "message": null
}
```

### Error (4xx, 5xx)

```json
{
    "success": false,
    "error": {
        "code": "NOT_FOUND",
        "message": "Package not found",
        "status": 404
    },
    "data": { "resource_type": "package", "resource_id": 123 }
}
```

### Validation (422)

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "status": 422
    },
    "data": {
        "errors": {
            "email": ["Email must be unique"],
            "phone": ["Phone must be valid"]
        }
    }
}
```

### Server Error (500)

**Production/Sandbox**:
```json
{
    "success": false,
    "error": {
        "code": "INTERNAL_SERVER_ERROR",
        "message": "An error occurred",
        "status": 500
    },
    "data": { "error_id": "ERROR-xyz-123456" }
}
```

**Local**:
```json
{
    "success": false,
    "error": {
        "code": "INTERNAL_SERVER_ERROR",
        "message": "Exception message",
        "status": 500
    },
    "data": { "exception": "...", "trace": "..." }
}
```

## Locale & Translations

The `X-Locale` header determines the language:

```bash
curl -H "X-Locale: en" https://api.example.com/packages/123
# "message": "Package not found"

curl -H "X-Locale: fr" https://api.example.com/packages/123
# "message": "Colis non trouvé"
```

Default locale: `en`

