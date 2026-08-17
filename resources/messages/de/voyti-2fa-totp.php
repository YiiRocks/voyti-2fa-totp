<?php

declare(strict_types=1);

return [
    // TotpTwoFactorMethod
    'voyti-2fa-totp.view.two_factor_totp.button_label' => 'Authenticator-App',
    'voyti-2fa-totp.view.two_factor_totp.method_name' => 'Authenticator-App',

    // TOTP setup fragment
    'voyti-2fa-totp.view.two_factor.scan_qr' => 'Scannen Sie diesen QR-Code mit Ihrer Authenticator-App',
    'voyti-2fa-totp.view.two_factor.manual_entry' => 'Oder geben Sie diesen Schlüssel manuell ein:',
    'voyti-2fa-totp.view.two_factor.renew' => 'Erneuern',
    'voyti-2fa-totp.view.two_factor.renew_error' => 'Es konnte kein neuer Schlüssel erzeugt werden. Bitte versuchen Sie es erneut.',
    'voyti-2fa-totp.view.two_factor.already_enabled' => 'Die Zwei-Faktor-Authentifizierung ist bereits aktiviert.',

    // CodeValidator
    'voyti-2fa-totp.validator.two_factor_not_configured' => 'Die Zwei-Faktor-Authentifizierung ist nicht konfiguriert.',
    'voyti-2fa-totp.validator.invalid_verification_code' => 'Ungültiger Bestätigungscode.',
];
