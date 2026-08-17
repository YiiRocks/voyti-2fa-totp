<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Json\Json;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var array{
 *     qrCodeUri: string,
 *     secret: string,
 *     renewLabel: string,
 *     renewUrl: string,
 *     renewErrorMessage: string,
 *     manualEntryLabel: string,
 *     formSubmitUrl: string
 * } $data
 * @var TranslatorInterface $translator
 * @var Csrf $csrf
 */

echo Html::p($translator->translate('voyti-2fa-totp.view.two_factor.scan_qr', category: 'voyti-2fa-totp'));

echo Html::div($data['qrCodeUri'])
    ->id('voyti-2fa-qr')
    ->class('img-fluid mb-3')
    ->addStyle(['max-width' => '260px'])
    ->encode(false);

$renewButton = Html::button('&#8635;')
    ->id('voyti-2fa-renew')
    ->class('btn', 'btn-outline-secondary', 'btn-sm', 'ms-2')
    ->attribute('title', $data['renewLabel'])
    ->attribute('aria-label', $data['renewLabel'])
    ->encode(false)
    ->render();
echo Html::p($data['manualEntryLabel'] . ' ' . Html::code($data['secret'])->id('voyti-2fa-secret')->render() . $renewButton)->encode(false);

$renewConfig = [
    // Json::encode() only reads public properties via get_object_vars(), so passing
    // the Csrf object itself would silently serialize as {} - force the string value.
    'csrfToken' => (string) $csrf,
    'renewUrl' => $data['renewUrl'],
    'renewErrorMessage' => $data['renewErrorMessage'],
];
$renewConfigJson = Json::htmlEncode($renewConfig);

// The fragment is re-injected on every method switch, so the handler is guarded per-element
// (the freshly-injected button lacks the marker and gets bound anew).
$js = <<<JS
    (() => {
        const cfg = {$renewConfigJson};

        const button = document.getElementById('voyti-2fa-renew');
        if (!button || button.dataset.voytiTotpRenewBound) {
            return;
        }
        button.dataset.voytiTotpRenewBound = '1';

        button.addEventListener('click', async () => {
            button.disabled = true;

            try {
                const response = await fetch(cfg.renewUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                    },
                    body: new URLSearchParams({
                        _csrf: cfg.csrfToken,
                    }),
                });

                if (!response.ok) {
                    throw new Error();
                }

                const data = await response.json();

                if (data.qrCodeUri) {
                    const qr = document.getElementById('voyti-2fa-qr');
                    if (qr) {
                        qr.innerHTML = data.qrCodeUri;
                    }
                }

                if (data.secret) {
                    const secret = document.getElementById('voyti-2fa-secret');
                    if (secret) {
                        secret.textContent = data.secret;
                    }
                }
            } catch {
                alert(cfg.renewErrorMessage);
            } finally {
                button.disabled = false;
            }
        });
    })();
    JS;

echo Html::script($js)->render();

/** @psalm-suppress InvalidScope */
echo $this->render('./_code-form', ['form' => $form, 'formSubmitUrl' => $data['formSubmitUrl'], 'translator' => $translator, 'csrf' => $csrf]);
