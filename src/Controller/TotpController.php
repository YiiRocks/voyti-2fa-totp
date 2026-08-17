<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\TwoFactor\Totp\Controller;

use Composer\InstalledVersions;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use YiiRocks\Voyti\Controller\RedirectTrait;
use YiiRocks\Voyti\Controller\RenderTrait;
use YiiRocks\Voyti\Model\User;
use YiiRocks\Voyti\Service\FlashNotifier;
use YiiRocks\Voyti\TwoFactor\Form\TwoFactorCodeForm;
use YiiRocks\Voyti\TwoFactor\Helper\Views\IndexView;
use YiiRocks\Voyti\TwoFactor\Model\UserTwoFactor;
use YiiRocks\Voyti\TwoFactor\Service\BackupCodeService;
use YiiRocks\Voyti\TwoFactor\Totp\Service\QrCodeUriGeneratorService;
use YiiRocks\Voyti\TwoFactor\TwoFactorMethodInterface;
use YiiRocks\Voyti\TwoFactor\TwoFactorMethodRegistry;
use YiiRocks\Voyti\VoytiConfig;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;
use Yiisoft\Json\Json;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Manages TOTP two-factor setup for the current user: renders the QR-code setup screen (or just
 * its fragment for the settings page's lazy-loading JavaScript) and issues a fresh secret on
 * renewal. Enabling/disabling itself stays generic in voyti-2fa's `TwoFactorController`; the user's
 * 2FA state lives in {@see UserTwoFactor}.
 */
final readonly class TotpController
{
    use RedirectTrait;
    use RenderTrait;

    public function __construct(
        private TranslatorInterface $translator,
        private WebViewRenderer $viewRenderer,
        private UrlGeneratorInterface $url,
        private VoytiConfig $config,
        private CurrentUser $currentUser,
        private ResponseFactoryInterface $responseFactory,
        private FlashNotifier $flashNotifier,
        private BackupCodeService $backupCodeService,
        private TwoFactorMethodRegistry $twoFactorMethods,
        private QrCodeUriGeneratorService $twoFactorQrCodeService,
    ) {}

    public function renew(): ResponseInterface
    {
        /** @var User $user */
        $user = $this->currentUser->getIdentity();

        if (UserTwoFactor::forUser($user)->isEnabled()) {
            return $this->jsonErrorResponse(Status::FORBIDDEN, 'voyti-2fa-totp.view.two_factor.already_enabled');
        }

        $qrCodeSvg = $this->twoFactorQrCodeService->regenerateQrCodeSvg($user);

        $response = $this->responseFactory->createResponse(Status::OK)
            ->withHeader(Header::CONTENT_TYPE, 'application/json; charset=UTF-8');
        $response->getBody()->write(Json::encode([
            'qrCodeUri' => $qrCodeSvg,
            'secret' => UserTwoFactor::forUser($user)->getSecret(),
        ]));

        return $response;
    }

    public function settings(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User $user */
        $user = $this->currentUser->getIdentity();

        if (UserTwoFactor::forUser($user)->isEnabled()) {
            return $this->redirect($this->url->generate('voyti/user-two-factor'));
        }

        $method = $this->twoFactorMethods->get('totp');

        if (strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest') {
            return $this->renderFragment('two-factor/_totp', [
                'data' => [
                    'qrCodeUri' => $this->twoFactorQrCodeService->generateQrCodeSvg($user),
                    /** @infection-ignore-all Cast ensures non-null string; unobservable since tests always set secret via createUserTwoFactor. */
                    'secret' => (string) UserTwoFactor::forUser($user)->getSecret(),
                    'renewLabel' => $this->translator->translate('voyti-2fa-totp.view.two_factor.renew', category: 'voyti-2fa-totp'),
                    'renewUrl' => $this->url->generate('voyti/user-two-factor-totp-renew'),
                    'renewErrorMessage' => $this->translator->translate('voyti-2fa-totp.view.two_factor.renew_error', category: 'voyti-2fa-totp'),
                    'manualEntryLabel' => $this->translator->translate('voyti-2fa-totp.view.two_factor.manual_entry', category: 'voyti-2fa-totp'),
                    'formSubmitUrl' => $this->url->generate('voyti/user-two-factor-enable'),
                ],
                'form' => new TwoFactorCodeForm($this->translator, $method->getName()),
            ]);
        }

        return $this->renderView('two-factor/index', [
            'coreViews' => $this->coreViewPath(),
            /** @infection-ignore-all The index template only uses `$form` in the enabled-user branch (disable form); this screen only ever shows non-enabled users, so the value is unobservable here. */
            'form' => new TwoFactorCodeForm($this->translator, $method->getName()),
            'data' => IndexView::create(
                UserTwoFactor::forUser($user)->isEnabled(),
                $method,
                [],
                /** @infection-ignore-all codeDelivered only affects the disable-confirmation flow, which needs an enabled user; this setup screen only ever shows non-enabled users, so the value is unobservable. */
                false,
                $this->backupCodeService->hasUnused($user),
                $this->renderSetupFragment($user, $method),
                $this->twoFactorMethods->getAvailable(),
                $this->config,
                $this->url,
                $this->translator(),
            ),
        ]);
    }

    /**
     * Absolute base path of the voyti-2fa package's views, where the generic `two-factor/index` page
     * this controller composes lives.
     */
    private function baseViewPath(): string
    {
        /** @var non-empty-string $basePath */
        $basePath = InstalledVersions::getInstallPath('yiirocks/voyti-2fa');

        return $basePath . '/resources/views/' . $this->config->webTheme->value;
    }

    /**
     * Absolute base path of the core module's views, whose shared chrome (menu, flash) the composed
     * `two-factor/index` page includes via its `$coreViews` variable.
     */
    private function coreViewPath(): string
    {
        /** @var non-empty-string $corePath */
        $corePath = InstalledVersions::getInstallPath('yiirocks/voyti');

        return $corePath . '/resources/views/' . $this->config->webTheme->value;
    }

    private function jsonErrorResponse(int $status, string $messageKey): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status)
            ->withHeader(Header::CONTENT_TYPE, 'application/json; charset=UTF-8');
        $response->getBody()->write(Json::encode([
            'error' => $this->translator->translate($messageKey, category: 'voyti-2fa-totp'),
        ]));

        return $response;
    }

    private function renderSetupFragment(User $user, TwoFactorMethodInterface $method): string
    {
        return (string) $this->renderFragment('two-factor/_totp', [
            'form' => new TwoFactorCodeForm($this->translator, $method->getName()),
            'data' => [
                'qrCodeUri' => $this->twoFactorQrCodeService->generateQrCodeSvg($user),
                /** @infection-ignore-all Cast ensures non-null string; unobservable since tests always set secret via createUserTwoFactor. */
                'secret' => (string) UserTwoFactor::forUser($user)->getSecret(),
                'renewLabel' => $this->translator->translate('voyti-2fa-totp.view.two_factor.renew', category: 'voyti-2fa-totp'),
                'renewUrl' => $this->url->generate('voyti/user-two-factor-totp-renew'),
                'renewErrorMessage' => $this->translator->translate('voyti-2fa-totp.view.two_factor.renew_error', category: 'voyti-2fa-totp'),
                'manualEntryLabel' => $this->translator->translate('voyti-2fa-totp.view.two_factor.manual_entry', category: 'voyti-2fa-totp'),
                'formSubmitUrl' => $this->url->generate('voyti/user-two-factor-enable'),
            ],
        ])->getBody();
    }

    /**
     * Shadows {@see RenderTrait::resolveViewPath()} to look in this package's bundled views first
     * (for the `two-factor/_totp` fragment and its `_code-form` sibling), then the host's override
     * path, then voyti-2fa's views for the generic `two-factor/index` page it composes.
     */
    private function resolveViewPath(string $view): string
    {
        $pluginPath = dirname(__DIR__, 2) . '/resources/views/' . $this->config->webTheme->value;
        if (is_file($pluginPath . '/' . $view . '.php')) {
            return $pluginPath;
        }

        if ($this->config->viewPath !== null && is_file($this->config->viewPath . '/' . $view . '.php')) {
            return $this->config->viewPath;
        }

        return $this->baseViewPath();
    }
}
