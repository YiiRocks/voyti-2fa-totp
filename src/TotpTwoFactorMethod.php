<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\TwoFactor\Totp;

use Override;
use YiiRocks\Voyti\Model\User;
use YiiRocks\Voyti\TwoFactor\Totp\Validator\CodeValidator;
use YiiRocks\Voyti\TwoFactor\TwoFactorMethodInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * TOTP authenticator-app two-factor method (the `yiirocks/voyti-2fa-totp` package): verification runs
 * against the user's stored base32 secret via {@see CodeValidator}, the settings screen shows a
 * scannable QR code provisioned by the package's own controller, and - being code-based - login
 * confirmation reuses the core's inline code form rather than a client-collected fragment.
 */
final readonly class TotpTwoFactorMethod implements TwoFactorMethodInterface
{
    public function __construct(
        private CodeValidator $codeValidator,
    ) {}

    #[Override]
    public function getButtonLabel(TranslatorInterface $translator): string
    {
        return $translator->translate('voyti-2fa-totp.view.two_factor_totp.button_label', category: 'voyti-2fa-totp');
    }

    #[Override]
    public function getConfirmFragmentUrl(UrlGeneratorInterface $url): ?string
    {
        return null;
    }

    #[Override]
    public function getEnabledWithMethodName(TranslatorInterface $translator): string
    {
        return $translator->translate('voyti-2fa-totp.view.two_factor_totp.method_name', category: 'voyti-2fa-totp');
    }

    #[Override]
    public function getErrorMessage(): string
    {
        return $this->codeValidator->getErrorMessage();
    }

    #[Override]
    public function getName(): string
    {
        return 'totp';
    }

    #[Override]
    public function getReauthFragmentUrl(UrlGeneratorInterface $url): ?string
    {
        // Code-based: the settings screen re-authenticates by prompting for a typed code inline.
        return null;
    }

    #[Override]
    public function getSettingsUrl(UrlGeneratorInterface $url): string
    {
        return $url->generate('voyti/user-two-factor-totp');
    }

    #[Override]
    public function isAvailable(): bool
    {
        return true;
    }

    #[Override]
    public function isCodeBased(): bool
    {
        return true;
    }

    #[Override]
    public function onAuthenticationStepStart(User $user): void {}

    #[Override]
    public function onDisable(User $user): void {}

    #[Override]
    public function requiresCodeDelivery(): bool
    {
        return false;
    }

    #[Override]
    public function verify(User $user, array $data): bool
    {
        return $this->codeValidator->validate($user, $data['code'] ?? '');
    }
}
