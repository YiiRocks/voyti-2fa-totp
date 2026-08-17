<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\TwoFactor\Totp\tests\Controller;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Container\ContainerInterface;
use YiiRocks\Voyti\Model\User;
use YiiRocks\Voyti\TwoFactor\Model\UserTwoFactor;
use YiiRocks\Voyti\TwoFactor\Totp\Controller\TotpController;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\CurrentUserTrait;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\DatabaseSetupTrait;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\TestContainerTrait;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\UserFactoryTrait;
use YiiRocks\Voyti\TwoFactor\Totp\tests\Support\VoytiConfigFactory;
use YiiRocks\Voyti\TwoFactor\Totp\tests\TestCase;
use YiiRocks\Voyti\VoytiConfig;
use Yiisoft\Json\Json;
use Yiisoft\User\CurrentUser;

#[AllowMockObjectsWithoutExpectations]
final class TotpControllerTest extends TestCase
{
    use CurrentUserTrait;
    use DatabaseSetupTrait;
    use TestContainerTrait;
    use UserFactoryTrait;

    protected function setUp(): void
    {
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
    }

    public function testRenew(): void
    {
        $user = $this->createUser(username: 'totp_renew', email: 'totp_renew@example.com');
        $this->createUserTwoFactor((int) ($user->getId() ?? 0), enabled: false, method: 'totp', secret: '01234567890123456789012345678901');
        [, $controller] = $this->build($user);

        $result = $controller->renew();

        self::assertSame(200, $result->getStatusCode());
        self::assertSame('application/json; charset=UTF-8', $result->getHeaderLine('Content-Type'));
        $data = Json::decode((string) $result->getBody());
        self::assertIsString($data['qrCodeUri']);
        self::assertSvgContent($data['qrCodeUri']);
        self::assertIsString($data['secret']);

        $stored = UserTwoFactor::forUser($user)->getSecret();
        self::assertSame($data['secret'], $stored);
        self::assertNotSame('01234567890123456789012345678901', $stored);
    }

    public function testRenewWhenAlreadyEnabledForbidden(): void
    {
        $user = $this->createUser(username: 'totp_renew_enabled', email: 'totp_renew_enabled@example.com');
        $this->createUserTwoFactor((int) ($user->getId() ?? 0), enabled: true, method: 'totp');
        [, $controller] = $this->build($user);

        $result = $controller->renew();

        self::assertSame(403, $result->getStatusCode());
        self::assertSame('application/json; charset=UTF-8', $result->getHeaderLine('Content-Type'));
        $data = Json::decode((string) $result->getBody());
        self::assertSame('Two-factor authentication is already enabled.', $data['error']);
    }

    public function testSettings(): void
    {
        $user = $this->createUser(username: 'totp_settings', email: 'totp_settings@example.com');
        [, $controller] = $this->build($user);

        $html = (string) $controller->settings(new ServerRequest('GET', '/'))->getBody();
        self::assertStringContainsString('Two-Factor Authentication', $html);
        self::assertStringContainsString('<svg', $html);
        self::assertStringContainsString('</svg>', $html);
        self::assertStringContainsString('Authenticator app', $html);
        self::assertStringContainsString('user-two-factor-totp-renew', $html);

        // Scenario 2: an AJAX request returns only the fragment (no full-page chrome)
        $request = (new ServerRequest('GET', '/'))->withHeader('X-Requested-With', 'XMLHttpRequest');
        $html = (string) $controller->settings($request)->getBody();
        self::assertStringContainsString('<svg', $html);
        self::assertStringContainsString('</svg>', $html);
        self::assertStringContainsString('user-two-factor-totp-renew', $html);
        self::assertStringNotContainsString('<h1>', $html);
    }

    public function testSettingsUsesHostViewPathOverride(): void
    {
        $user = $this->createUser(username: 'totp_host_views', email: 'totp_host_views@example.com');

        $hostViews = sys_get_temp_dir() . '/voyti-totp-host-views-' . bin2hex(random_bytes(6));
        $indexView = $hostViews . '/two-factor/index.php';
        mkdir(dirname($indexView), 0o777, true);
        file_put_contents($indexView, '<?php echo "HOST_TWO_FACTOR_INDEX_OVERRIDE";');

        try {
            $html = (string) $this->createTestContainer([
                CurrentUser::class => $this->createCurrentUser($user),
                VoytiConfig::class => VoytiConfigFactory::create(viewPath: $hostViews),
            ])->get(TotpController::class)->settings(new ServerRequest('GET', '/'))->getBody();
            self::assertStringContainsString('HOST_TWO_FACTOR_INDEX_OVERRIDE', $html);

            // Scenario 2: a viewPath override without the view falls back to voyti-2fa's own view
            mkdir($hostViews . '/empty', 0o777, true);
            $html = (string) $this->createTestContainer([
                CurrentUser::class => $this->createCurrentUser($user),
                VoytiConfig::class => VoytiConfigFactory::create(viewPath: $hostViews . '/empty'),
            ])->get(TotpController::class)->settings(new ServerRequest('GET', '/'))->getBody();
            self::assertStringContainsString('Two-Factor Authentication', $html);
            self::assertStringNotContainsString('HOST_TWO_FACTOR_INDEX_OVERRIDE', $html);
        } finally {
            @unlink($indexView);
            @rmdir($hostViews . '/empty');
            @rmdir($hostViews . '/two-factor');
            @rmdir($hostViews);
        }
    }

    public function testSettingsWhenAlreadyEnabledRedirects(): void
    {
        $user = $this->createUser(username: 'totp_enabled', email: 'totp_enabled@example.com');
        $this->createUserTwoFactor((int) ($user->getId() ?? 0), enabled: true, method: 'totp');
        [, $controller] = $this->build($user);

        $result = $controller->settings(new ServerRequest('GET', '/'));

        self::assertSame(302, $result->getStatusCode());
        self::assertSame('//voyti/user-two-factor', $result->getHeaderLine('Location'));
    }

    /**
     * @return array{0: ContainerInterface, 1: TotpController}
     */
    private function build(User $user): array
    {
        $container = $this->createTestContainer([
            CurrentUser::class => $this->createCurrentUser($user),
        ]);

        return [$container, $container->get(TotpController::class)];
    }
}
