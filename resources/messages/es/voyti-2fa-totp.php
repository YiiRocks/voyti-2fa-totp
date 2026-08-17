<?php

declare(strict_types=1);

return [
    // TotpTwoFactorMethod
    'voyti-2fa-totp.view.two_factor_totp.button_label' => 'Aplicación de autenticación',
    'voyti-2fa-totp.view.two_factor_totp.method_name' => 'Aplicación de autenticación',

    // TOTP setup fragment
    'voyti-2fa-totp.view.two_factor.scan_qr' => 'Escanee este código QR con su aplicación de autenticación',
    'voyti-2fa-totp.view.two_factor.manual_entry' => 'O introduzca esta clave manualmente:',
    'voyti-2fa-totp.view.two_factor.renew' => 'Renovar',
    'voyti-2fa-totp.view.two_factor.renew_error' => 'No se pudo generar una nueva clave. Inténtelo de nuevo.',
    'voyti-2fa-totp.view.two_factor.already_enabled' => 'La autenticación de dos factores ya está habilitada.',

    // CodeValidator
    'voyti-2fa-totp.validator.two_factor_not_configured' => 'La autenticación de dos factores no está configurada.',
    'voyti-2fa-totp.validator.invalid_verification_code' => 'Código de verificación no válido.',
];
