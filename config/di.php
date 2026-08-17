<?php

declare(strict_types=1);

use YiiRocks\Voyti\TwoFactor\Totp\Controller\TotpController;
use YiiRocks\Voyti\TwoFactor\Totp\Service\QrCodeUriGeneratorService;
use YiiRocks\Voyti\TwoFactor\Totp\TotpTwoFactorMethod;
use YiiRocks\Voyti\TwoFactor\Totp\Validator\CodeValidator;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;

/** @var array $params */

return [
    QrCodeUriGeneratorService::class => QrCodeUriGeneratorService::class,
    CodeValidator::class => CodeValidator::class,

    // Registers the TOTP method with the core registry via the `voyti.two-factor-method` tag.
    TotpTwoFactorMethod::class => [
        'class' => TotpTwoFactorMethod::class,
        'tags' => ['voyti.two-factor-method'],
    ],
    TotpController::class => TotpController::class,

    // Translation category source for this package's message files.
    'yiirocks/voyti-2fa-totp.translator' => [
        'definition' => static fn() => new CategorySource(
            'voyti-2fa-totp',
            new MessageSource(dirname(__DIR__) . '/resources/messages'),
            new SimpleMessageFormatter(),
        ),
        'tags' => ['translation.categorySource'],
    ],
];
