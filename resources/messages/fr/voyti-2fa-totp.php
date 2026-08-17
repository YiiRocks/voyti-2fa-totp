<?php

declare(strict_types=1);

return [
    // TotpTwoFactorMethod
    'voyti-2fa-totp.view.two_factor_totp.button_label' => "Application d'authentification",
    'voyti-2fa-totp.view.two_factor_totp.method_name' => "Application d'authentification",

    // TOTP setup fragment
    'voyti-2fa-totp.view.two_factor.scan_qr' => 'Scannez ce code QR avec votre application d\'authentification',
    'voyti-2fa-totp.view.two_factor.manual_entry' => 'Ou saisissez cette clé manuellement :',
    'voyti-2fa-totp.view.two_factor.renew' => 'Renouveler',
    'voyti-2fa-totp.view.two_factor.renew_error' => 'Impossible de générer une nouvelle clé. Veuillez réessayer.',
    'voyti-2fa-totp.view.two_factor.already_enabled' => "L'authentification à deux facteurs est déjà activée.",

    // CodeValidator
    'voyti-2fa-totp.validator.two_factor_not_configured' => "L'authentification à deux facteurs n'est pas configurée.",
    'voyti-2fa-totp.validator.invalid_verification_code' => 'Code de vérification invalide.',
];
