<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\TwoFactor\Totp\tests\Service;

use chillerlan\Authenticator\Authenticator;
use YiiRocks\Voyti\TwoFactor\Model\UserTwoFactor;
use YiiRocks\Voyti\TwoFactor\Totp\Service\QrCodeUriGeneratorService;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\DatabaseSetupTrait;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\UserFactoryTrait;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\VoytiConfigFactory;
use YiiRocks\Voyti\TwoFactor\Totp\tests\TestCase;

final class QrCodeUriGeneratorServiceTest extends TestCase
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

    public function testGenerateWithExactlySixteenCharSecretReusesIt(): void
    {
        $secret = str_repeat('A', 16);
        $user = $this->createUserWithSecret($secret);

        $this->createService()->generateQrCodeSvg($user);

        self::assertSame($secret, UserTwoFactor::forUser($user)->getSecret());
    }

    public function testGenerateWithExistingSecretReusesIt(): void
    {
        $secret = (new Authenticator())->createSecret();
        $user = $this->createUserWithSecret($secret);

        $svg = $this->createService()->generateQrCodeSvg($user);

        self::assertSvgContent($svg);
        self::assertSame($secret, UserTwoFactor::forUser($user)->getSecret());
    }

    public function testGenerateWithFifteenCharSecretIssuesFreshOne(): void
    {
        $user = $this->createUserWithSecret(str_repeat('A', 15));

        $this->createService()->generateQrCodeSvg($user);

        self::assertNotSame(str_repeat('A', 15), UserTwoFactor::forUser($user)->getSecret());
    }

    public function testGenerateWithInvalidSecretIssuesAndPersistsFreshOne(): void
    {
        $user = $this->createUserWithSecret('6-digit-code');

        $svg = $this->createService()->generateQrCodeSvg($user);

        self::assertSvgContent($svg);
        $stored = (string) UserTwoFactor::forUser($user)->getSecret();
        self::assertNotSame('6-digit-code', $stored);
        self::assertMatchesRegularExpression('/^[2-7A-Z]+$/', $stored);
        self::assertGreaterThanOrEqual(16, strlen($stored));
    }

    public function testGenerateWithLeadingInvalidCharIssuesFreshOne(): void
    {
        $secret = '0' . str_repeat('A', 16);
        $user = $this->createUserWithSecret($secret);

        $this->createService()->generateQrCodeSvg($user);

        self::assertNotSame($secret, UserTwoFactor::forUser($user)->getSecret());
    }

    public function testGenerateWithTrailingInvalidCharIssuesFreshOne(): void
    {
        $secret = str_repeat('A', 16) . '0';
        $user = $this->createUserWithSecret($secret);

        $this->createService()->generateQrCodeSvg($user);

        self::assertNotSame($secret, UserTwoFactor::forUser($user)->getSecret());
    }

    public function testRegenerateAlwaysIssuesFreshSecret(): void
    {
        $secret = (new Authenticator())->createSecret();
        $user = $this->createUserWithSecret($secret);

        $svg = $this->createService()->regenerateQrCodeSvg($user);

        self::assertSvgContent($svg);
        $stored = (string) UserTwoFactor::forUser($user)->getSecret();
        self::assertNotSame($secret, $stored);
        self::assertMatchesRegularExpression('/^[2-7A-Z]+$/', $stored);
    }

    private function createService(): QrCodeUriGeneratorService
    {
        return new QrCodeUriGeneratorService(VoytiConfigFactory::create());
    }
}
