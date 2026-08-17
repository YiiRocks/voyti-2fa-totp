<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\TwoFactor\Totp\Validator;

use chillerlan\TwoFactorQRCode\TwoFactorQRCode;
use Throwable;
use YiiRocks\Voyti\Model\User;
use YiiRocks\Voyti\TwoFactor\Model\UserTwoFactor;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Verifies a TOTP two-factor authentication code against the user's stored base32 secret via
 * `chillerlan/2fa-qrcode-bundle`, exposing a translated error message via {@see getErrorMessage()}
 * on failure.
 */
final class CodeValidator
{
    private string $error = '';

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function getErrorMessage(): string
    {
        return $this->error;
    }

    public function validate(User $user, string $code, int $cycles = 1): bool
    {
        $secret = UserTwoFactor::forUser($user)->getSecret();
        if ($secret === null || $secret === '') {
            $this->error = $this->translator->translate(
                'voyti-2fa-totp.validator.two_factor_not_configured',
                category: 'voyti-2fa-totp',
            );
            return false;
        }

        try {
            $valid = (new TwoFactorQRCode(['adjacent' => $cycles]))
                ->setSecret($secret)
                ->verifyOTP($code, time());
        } catch (Throwable) {
            $valid = false;
        }

        if (!$valid) {
            $this->error = $this->translator->translate(
                'voyti-2fa-totp.validator.invalid_verification_code',
                category: 'voyti-2fa-totp',
            );
            return false;
        }

        return true;
    }
}
