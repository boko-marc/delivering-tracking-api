# Exceptions & API Responses

## Overview

All errors in the API are handled through a single `ApiException` class that returns JSON responses with a consistent schema. Exception handling is centralized in `bootstrap/app.php` and respects the application locale (set via `X-Locale` header).

## Exception Usage

### Pattern 1: Static Message (No Translation)

⚠️ Only for development or debugging.

```php
throw ApiException::notFound('User not found');
throw ApiException::badRequest('Invalid email format');
throw ApiException::unauthorized('Token expired');
throw ApiException::forbidden('Insufficient permissions');
```

This uses the provided text directly. It does not translate based on `X-Locale`.

### Pattern 2: Translate Message with a Translation Key (Recommended)

✅ Use this for production APIs.

```php
throw ApiException::notFound(
    message: __('errors.user_not_found')
);

throw ApiException::badRequest(
    message: __('errors.invalid_email')
);
```

Translation file `modules/shared/lang/fr/errors.php`:
```php
return [
    'user_not_found' => 'Utilisateur non trouvé',
    'invalid_email' => 'Email invalide',
];
```

The `__()` helper loads the translation for the current locale, using `X-Locale` or default `fr`.

### Pattern 3: Module Error Enum + Translation Key (Best Practice)

✅ Use this when your module defines domain-specific errors.

```php
use Modules\Delivery\Enums\DeliveryErrorCode;

throw ApiException::notFound(
    message: __(
        DeliveryErrorCode::PACKAGE_NOT_FOUND->translationKey()
    )
);
```

`DeliveryErrorCode::PACKAGE_NOT_FOUND->translationKey()` returns a translation key such as `errors.package_not_found`.

Translation file `modules/delivery/lang/fr/errors.php`:
```php
return [
    'package_not_found' => 'Colis non trouvé',
];
```

### With Data Payload

```php
throw ApiException::badRequest(
    message: __('errors.validation_failed'),
    data: ['field' => 'email', 'rule' => 'unique']
);
```

### Custom Status Code

```php
throw new ApiException(
    status: 429,
    codeEnum: ErrorCode::VALIDATION_ERROR,
    message: __('errors.too_many_requests'),
    data: ['retry_after' => 60]
);
```

## Response Schemas

### Success Response (2xx)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "name": "John Doe"
    },
    "message": "Operation successful"
}
```

### Error Response (4xx, 5xx)
```json
{
    "success": false,
    "error": {
        "code": "NOT_FOUND",
        "message": "Resource not found",
        "status": 404
    },
    "data": null
}
```

### Validation Error (422)
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

### Authentication Error (401)
```json
{
    "success": false,
    "error": {
        "code": "UNAUTHORIZED",
        "message": "Unauthenticated",
        "status": 401
    },
    "data": null
}
```

### Permission Error (403)
```json
{
    "success": false,
    "error": {
        "code": "FORBIDDEN",
        "message": "You don't have permission to access this resource",
        "status": 403
    },
    "data": null
}
```

### Server Error (500)
```json
{
    "success": false,
    "error": {
        "code": "INTERNAL_SERVER_ERROR",
        "message": "An unexpected error occurred",
        "status": 500
    },
    "data": {
        "debug": "Exception stack trace (only if APP_DEBUG=true)"
    }
}
```

## Global Exception Types Handled

The `bootstrap/app.php` handler automatically converts these to JSON:

- **`ApiException`** → formatted with custom code + message
- **`ValidationException`** → 422 with `VALIDATION_ERROR` code
- **`NotFoundHttpException`** (route binding) → 404 with `NOT_FOUND` code
- **`AuthenticationException`** → 401 with `UNAUTHORIZED` code
- **Any other exception** → 500 with `INTERNAL_SERVER_ERROR` code

## How to Create Module-Specific Error Codes

When a module needs custom error codes, follow these steps:

### Step 1: Create the Enum

File: `modules/{module}/src/Enums/{Module}ErrorCode.php`

```php
<?php

namespace Modules\Delivery\Enums;

enum DeliveryErrorCode: string
{
    case PACKAGE_NOT_FOUND = 'PACKAGE_NOT_FOUND';
    case INVALID_TRACKING_NUMBER = 'INVALID_TRACKING_NUMBER';
    case DELIVERY_WINDOW_EXPIRED = 'DELIVERY_WINDOW_EXPIRED';

    /**
     * Returns the translation key for this error code.
     * Keys must exist in modules/{module}/lang/{locale}/errors.php
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::PACKAGE_NOT_FOUND => 'errors.package_not_found',
            self::INVALID_TRACKING_NUMBER => 'errors.invalid_tracking_number',
            self::DELIVERY_WINDOW_EXPIRED => 'errors.delivery_window_expired',
        };
    }
}
```

### Step 2: Add French Translations

File: `modules/{module}/lang/fr/errors.php`

```php
<?php

// Keys must match the translationKey() method return values
return [
    'package_not_found' => 'Colis non trouvé',
    'invalid_tracking_number' => 'Numéro de suivi invalide',
    'delivery_window_expired' => 'Fenêtre de livraison expirée',
];
```

### Step 3: Use in Code

```php
<?php

namespace Modules\Delivery\Http\Controllers;

use Modules\Delivery\Enums\DeliveryErrorCode;
use Modules\Shared\Exceptions\ApiException;

class PackageController
{
    public function show($id)
    {
        $package = Package::find($id);
        
        if (!$package) {
            // Use enum's translationKey() to get the translation key
            // Pass it to __() helper to get translated message
            throw ApiException::notFound(
                message: __(
                    DeliveryErrorCode::PACKAGE_NOT_FOUND->translationKey()
                )
            );
        }
        
        return ApiResponse::success(data: $package);
    }
    
    public function store(Request $request)
    {
        $request->validate(['tracking_number' => 'required|string|unique:packages']);
        
        if (!isValidTrackingNumber($request->tracking_number)) {
            throw ApiException::badRequest(
                message: __(
                    DeliveryErrorCode::INVALID_TRACKING_NUMBER->translationKey()
                )
            );
        }
        
        // ...
    }
}
```

**Summary**: Enum cases define `translationKey()` → keys exist in translation files → use `__($enum->translationKey())` in exception throws → automatic multi-language support.

## Locale Handling

The API respects the `X-Locale` header to determine error message language:

```bash
curl -H "X-Locale: en" https://api.example.com/packages/123
# Returns English error messages

curl -H "X-Locale: fr" https://api.example.com/packages/123
# Returns French error messages
```

Default locale is `fr` if header is omitted.

## Testing

### Test Error Responses

```php
use Modules\Delivery\Enums\DeliveryErrorCode;

test('returns 404 with translated message for missing package', function () {
    $response = $this->getJson('/api/packages/999', [
        'X-Locale' => 'fr'
    ]);
    
    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Colis non trouvé', // ✅ French translation
                'status' => 404,
            ],
        ]);
});

test('returns 404 with English translation', function () {
    $response = $this->getJson('/api/packages/999', [
        'X-Locale' => 'en'
    ]);
    
    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Package not found', // ✅ English translation
                'status' => 404,
            ],
        ]);
});

test('returns 422 for validation errors', function () {
    $response = $this->postJson('/api/packages', [
        'tracking_number' => '', // required
    ], [
        'X-Locale' => 'fr'
    ]);
    
    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'status' => 422,
            ],
        ])
        ->assertJsonPath('data.errors.tracking_number', fn($errors) => count($errors) > 0);
});
```

**Key points**:
- Always include `X-Locale` header in tests to verify translation
- Assert the error `code` matches enum case value (e.g., `NOT_FOUND`, `VALIDATION_ERROR`)
- Assert the `message` is the correct translation for that locale

## Best Practices

1. **Always throw** `ApiException` in controllers/services, never return error values
2. **Use static helpers** (`notFound`, `badRequest`, etc.) for common HTTP statuses
3. **Include translation keys** when throwing exceptions with custom messages
4. **Pass relevant data** to help clients understand the error context
5. **Test all code paths** that throw exceptions to verify JSON schema
6. **Set X-Locale header** in tests to verify multi-language support
