<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\TwoFactor\Totp\Service;

use chillerlan\TwoFactorQRCode\TwoFactorQRCode;
use YiiRocks\Voyti\Model\User;
use YiiRocks\Voyti\TwoFactor\Model\UserTwoFactor;
use YiiRocks\Voyti\VoytiConfig;

/**
 * Builds QR-code SVGs for TOTP setup. `generateQrCodeSvg()` reuses the user's existing
 * {@see UserTwoFactor} secret when it's a plausible TOTP secret, while `regenerateQrCodeSvg()`
 * always issues a fresh secret.
 */
final readonly class QrCodeUriGeneratorService
{
    public function __construct(
        private VoytiConfig $config,
    ) {}

    public function generateQrCodeSvg(User $user): string
    {
        return $this->buildSvg($user, UserTwoFactor::forUser($user)->getSecret());
    }

    public function regenerateQrCodeSvg(User $user): string
    {
        return $this->buildSvg($user, null);
    }

    private function buildSvg(User $user, ?string $secret): string
    {
        $secret = $this->resolveSecret($user, $secret);

        $twoFactor = new TwoFactorQRCode([
            'outputBase64' => false,
            /** @infection-ignore-all Path-connection is a purely cosmetic rendering option; any value still yields a valid scannable QR. */
            'connectPaths' => true,
            /** @infection-ignore-all The module scale is a purely cosmetic rendering option; any value still yields a valid scannable QR. */
            'scale' => 4,
            'svgAddXmlHeader' => false,
        ]);
        $twoFactor->setSecret($secret);

        return $twoFactor->getQRCode($user->getEmail(), $this->config->appName);
    }

    /**
     * A stored value is only reused as the TOTP secret when it looks like one: base32 of a
     * reasonable length. Anything else (e.g. a leftover six-digit email-code in the shared
     * {@see UserTwoFactor} secret column, or an empty value) is discarded in favour of a fresh secret.
     */
    private function isValidTotpSecret(?string $secret): bool
    {
        return $secret !== null
            && strlen($secret) >= 16
            && preg_match('/^[2-7A-Z]+$/', $secret) === 1;
    }

    private function resolveSecret(User $user, ?string $secret): string
    {
        if ($this->isValidTotpSecret($secret)) {
            /** @var non-empty-string $secret */
            return $secret;
        }

        $secret = (new TwoFactorQRCode())->createSecret();
        $twoFactor = UserTwoFactor::forUser($user);
        $twoFactor->setSecret($secret);
        $twoFactor->save();

        return $secret;
    }
}
