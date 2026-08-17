<?php

declare(strict_types=1);

return [
    // TotpTwoFactorMethod
    'voyti-2fa-totp.view.two_factor_totp.button_label' => 'Приложение-аутентификатор',
    'voyti-2fa-totp.view.two_factor_totp.method_name' => 'Приложение-аутентификатор',

    // TOTP setup fragment
    'voyti-2fa-totp.view.two_factor.scan_qr' => 'Сканируйте этот QR-код с помощью приложения-аутентификатора',
    'voyti-2fa-totp.view.two_factor.manual_entry' => 'Или введите этот ключ вручную:',
    'voyti-2fa-totp.view.two_factor.renew' => 'Обновить',
    'voyti-2fa-totp.view.two_factor.renew_error' => 'Не удалось создать новый ключ. Пожалуйста, попробуйте снова.',
    'voyti-2fa-totp.view.two_factor.already_enabled' => 'Двухфакторная аутентификация уже включена.',

    // CodeValidator
    'voyti-2fa-totp.validator.two_factor_not_configured' => 'Двухфакторная аутентификация не настроена.',
    'voyti-2fa-totp.validator.invalid_verification_code' => 'Неверный код проверки.',
];
