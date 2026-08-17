<?php

declare(strict_types=1);

return [
    // TotpTwoFactorMethod
    'voyti-2fa-totp.view.two_factor_totp.button_label' => 'Authenticator app',
    'voyti-2fa-totp.view.two_factor_totp.method_name' => 'Authenticator app',

    // TOTP setup fragment
    'voyti-2fa-totp.view.two_factor.scan_qr' => 'Scan this QR code with your authenticator app',
    'voyti-2fa-totp.view.two_factor.manual_entry' => 'Or enter this key manually:',
    'voyti-2fa-totp.view.two_factor.renew' => 'Renew',
    'voyti-2fa-totp.view.two_factor.renew_error' => 'Could not generate a new key. Please try again.',
    'voyti-2fa-totp.view.two_factor.already_enabled' => 'Two-factor authentication is already enabled.',

    // CodeValidator
    'voyti-2fa-totp.validator.two_factor_not_configured' => 'Two factor authentication is not configured.',
    'voyti-2fa-totp.validator.invalid_verification_code' => 'Invalid verification code.',
];
