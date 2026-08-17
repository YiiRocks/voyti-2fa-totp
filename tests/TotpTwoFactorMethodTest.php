<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\TwoFactor\Totp\tests;

use chillerlan\Authenticator\Authenticator;
use YiiRocks\Voyti\Model\User;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\DatabaseSetupTrait;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\FakeUrlGenerator;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\UserFactoryTrait;
use YiiRocks\Voyti\TwoFactor\Totp\TotpTwoFactorMethod;
use YiiRocks\Voyti\TwoFactor\Totp\Validator\CodeValidator;

final class TotpTwoFactorMethodTest extends TestCase
{
    use DatabaseSetupTrait;
    use UserFactoryTrait;

    protected function setUp(): void
    {
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
    }

    public function testAuthenticationHooksAreNoOps(): void
    {
        $method = $this->createMethod();
        $user = new User();

        $method->onAuthenticationStepStart($user);
        $method->onDisable($user);

        self::assertSame('', $method->getErrorMessage());
    }

    public function testStaticIdentity(): void
    {
        $method = $this->createMethod();
        $url = new FakeUrlGenerator();
        $url->setUrl('voyti/user-two-factor-totp', '/settings/two-factor/totp');

        self::assertSame('totp', $method->getName());
        self::assertTrue($method->isAvailable());
        self::assertTrue($method->isCodeBased());
        self::assertFalse($method->requiresCodeDelivery());
        self::assertNull($method->getConfirmFragmentUrl($url));
        self::assertNull($method->getReauthFragmentUrl($url));
        self::assertSame('/settings/two-factor/totp', $method->getSettingsUrl($url));
        self::assertSame('Authenticator app', $method->getButtonLabel($this->createTranslator()));
        self::assertSame('Authenticator app', $method->getEnabledWithMethodName($this->createTranslator()));
    }

    public function testVerify(): void
    {
        $method = $this->createMethod();
        $secret = (new Authenticator())->createSecret();
        $user = $this->createUserWithSecret($secret);

        // Scenario 1: correct code verifies
        $code = (new Authenticator())->setSecret($secret)->code(time());
        self::assertTrue($method->verify($user, ['code' => $code]));
        self::assertSame('', $method->getErrorMessage());

        // Scenario 2: wrong code fails and exposes the translated error
        self::assertFalse($method->verify($user, ['code' => '000000']));
        self::assertSame('Invalid verification code.', $method->getErrorMessage());

        // Scenario 3: missing code key is treated as an empty string
        self::assertFalse($method->verify($user, []));
    }

    private function createMethod(): TotpTwoFactorMethod
    {
        return new TotpTwoFactorMethod(new CodeValidator($this->createTranslator()));
    }
}
