<?php

/**
 * TEMPLATE: Module Error Translations
 *
 * Usage: Agent generates actual files by:
 * 1. Replace {module} with module name (e.g., delivery, iam, notifications)
 * 2. Replace {locale} with language code (e.g., fr, en, es)
 * 3. Replace error descriptions with translations that match {Module}ErrorCode enum keys
 * 4. Save as modules/{module}/lang/{locale}/errors.php
 * 5. Ensure all enum cases in {Module}ErrorCode have a corresponding translation key
 *
 * @example
 * // modules/delivery/lang/fr/errors.php
 * return [
 *     'package_not_found' => 'Colis non trouvé',
 *     'invalid_tracking_number' => 'Numéro de suivi invalide',
 * ];
 * @example
 * // modules/delivery/lang/en/errors.php
 * return [
 *     'package_not_found' => 'Package not found',
 *     'invalid_tracking_number' => 'Invalid tracking number',
 * ];
 */

return [
    // Error translations for {module} module
    // Keys must match the translationKey() method in Modules\{Module}\Enums\{Module}ErrorCode
    'error_one' => 'Translation for error one in {locale}',
    'error_two' => 'Translation for error two in {locale}',
    'error_three' => 'Translation for error three in {locale}',
];
