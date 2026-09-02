<?php

declare(strict_types=1);

use YiiRocks\Voyti\TwoFactor\Totp\Controller\TotpController;
use Yiisoft\Router\Route;

return [
    'yiirocks/voyti' => [
        '2fa' => [
            'methodRoutes' => [
                Route::get('two-factor/totp/')
                    ->name('voyti/user-two-factor-totp')
                    ->action([TotpController::class, 'settings']),
                Route::post('two-factor/totp/renew')
                    ->name('voyti/user-two-factor-totp-renew')
                    ->action([TotpController::class, 'renew']),
            ],
        ],
    ],
];
