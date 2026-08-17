<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\TwoFactor\Totp\tests\Validator;

use chillerlan\Authenticator\Authenticator;
use PHPUnit\Framework\Attributes\DataProvider;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\DatabaseSetupTrait;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\UserFactoryTrait;
use YiiRocks\Voyti\TwoFactor\Totp\tests\TestCase;
use YiiRocks\Voyti\TwoFactor\Totp\Validator\CodeValidator;

final class CodeValidatorTest extends TestCase
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

    public static function missingSecretProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
        ];
    }

    public function testValidate(): void
    {
        $validator = new CodeValidator($this->createTranslator());
        $secret = (new Authenticator())->createSecret();
        $user = $this->createUserWithSecret($secret);

        // Scenario 1: correct code is accepted
        $code = (new Authenticator())->setSecret($secret)->code(time());
        self::assertTrue($validator->validate($user, $code));
        self::assertSame('', $validator->getErrorMessage());

        // Scenario 2: a code from an adjacent time window is accepted
        $adjacentCode = (new Authenticator())->setSecret($secret)->code(time() - 30);
        self::assertTrue($validator->validate($user, $adjacentCode));

        // Scenario 3: a code from outside the default window (two steps back) is rejected
        $staleCode = (new Authenticator())->setSecret($secret)->code(time() - 60);
        self::assertFalse($validator->validate($user, $staleCode));

        // Scenario 4: the same code is accepted once the window is explicitly widened
        self::assertTrue($validator->validate($user, $staleCode, 2));
        $futureCode = (new Authenticator())->setSecret($secret)->code(time() + 60);
        self::assertTrue($validator->validate($user, $futureCode, 2));

        // Scenario 5: wrong code is rejected with a translated message
        self::assertFalse($validator->validate($user, '000000'));
        self::assertSame('Invalid verification code.', $validator->getErrorMessage());
    }

    public function testValidateWithGarbageSecret(): void
    {
        $validator = new CodeValidator($this->createTranslator());
        $user = $this->createUserWithSecret('not-base32!');

        self::assertFalse($validator->validate($user, '123456'));
        self::assertSame('Invalid verification code.', $validator->getErrorMessage());
    }

    #[DataProvider('missingSecretProvider')]
    public function testValidateWithoutConfiguredSecret(?string $secret): void
    {
        $validator = new CodeValidator($this->createTranslator());
        $user = $this->createUserWithSecret($secret);

        self::assertFalse($validator->validate($user, '123456'));
        self::assertSame('Two factor authentication is not configured.', $validator->getErrorMessage());
    }
}
