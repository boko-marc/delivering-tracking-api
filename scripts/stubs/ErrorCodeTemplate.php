<?php

/**
 * TEMPLATE: Module ErrorCode Enum
 *
 * Usage: Agent generates actual files by:
 * 1. Replace {Module} with module name (e.g., Delivery, Iam, Notifications)
 * 2. Replace case declarations and translationKey() matches with domain-specific errors
 * 3. Save as modules/{module}/src/Enums/{Module}ErrorCode.php
 *
 * @example
 * namespace Modules\Delivery\Enums;
 *
 * enum DeliveryErrorCode: string
 * {
 *     case PACKAGE_NOT_FOUND = 'PACKAGE_NOT_FOUND';
 *     case INVALID_TRACKING_NUMBER = 'INVALID_TRACKING_NUMBER';
 *
 *     public function translationKey(): string
 *     {
 *         return match ($this) {
 *             self::PACKAGE_NOT_FOUND => 'errors.package_not_found',
 *             self::INVALID_TRACKING_NUMBER => 'errors.invalid_tracking_number',
 *         };
 *     }
 * }
 */

