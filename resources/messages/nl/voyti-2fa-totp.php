<?php

declare(strict_types=1);

return [
    // TotpTwoFactorMethod
    'voyti-2fa-totp.view.two_factor_totp.button_label' => 'Authenticator-app',
    'voyti-2fa-totp.view.two_factor_totp.method_name' => 'Authenticator-app',

    // TOTP setup fragment
    'voyti-2fa-totp.view.two_factor.scan_qr' => 'Scan deze QR-code met uw authenticator-app',
    'voyti-2fa-totp.view.two_factor.manual_entry' => 'Of voer deze sleutel handmatig in:',
    'voyti-2fa-totp.view.two_factor.renew' => 'Vernieuwen',
    'voyti-2fa-totp.view.two_factor.renew_error' => 'Er kon geen nieuwe sleutel worden gegenereerd. Probeer het opnieuw.',
    'voyti-2fa-totp.view.two_factor.already_enabled' => 'Tweefactorauthenticatie is al ingeschakeld.',

    // CodeValidator
    'voyti-2fa-totp.validator.two_factor_not_configured' => 'Tweefactorauthenticatie is niet geconfigureerd.',
    'voyti-2fa-totp.validator.invalid_verification_code' => 'Ongeldige verificatiecode.',
];
