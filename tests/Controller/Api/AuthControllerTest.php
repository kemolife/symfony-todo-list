<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createUser(string $email, string $password): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function postJson(string $uri, array $body): void
    {
        $this->client->request(
            'POST',
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body),
        );
    }

    public function testRegisterCreatesUserAndReturnsToken(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'new@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
    }

    public function testRegisterFailsWithInvalidEmail(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'not-an-email',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterFailsWhenEmailAlreadyTaken(): void
    {
        $this->createUser('existing@test.com', 'Password1!');

        $this->postJson('/api/auth/register', [
            'email' => 'existing@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testLoginReturnsToken(): void
    {
        $this->createUser('login@test.com', 'Password1!');

        $this->postJson('/api/auth/login', [
            'email' => 'login@test.com',
            'password' => 'Password1!',
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $this->createUser('user@test.com', 'Password1!');

        $this->postJson('/api/auth/login', [
            'email' => 'user@test.com',
            'password' => 'WrongPassword1!',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAdminLoginReturnsPreAuthToken(): void
    {
        $adminSecret = $_ENV['ADMIN_SECRET'] ?: '9575806574';

        $this->postJson('/api/admin/register', [
            'email' => 'admin2fa@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'admin_secret' => $adminSecret,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin2fa@test.com',
            'password' => 'Password1!',
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['two_factor_required']);
        self::assertNotEmpty($data['pre_auth_token']);
    }

    public function test2faCheckReturnsTokenWithValidCode(): void
    {
        $adminSecret = $_ENV['ADMIN_SECRET'] ?: '9575806574';

        $this->postJson('/api/admin/register', [
            'email' => 'admin2fa2@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'admin_secret' => $adminSecret,
        ]);

        $registerData = json_decode($this->client->getResponse()->getContent(), true);
        $totpSecret = $registerData['totp_secret'];

        $this->postJson('/api/auth/login', [
            'email' => 'admin2fa2@test.com',
            'password' => 'Password1!',
        ]);

        $loginData = json_decode($this->client->getResponse()->getContent(), true);
        $preAuthToken = $loginData['pre_auth_token'];

        $code = TOTP::createFromSecret($totpSecret)->now();

        $this->postJson('/api/auth/2fa/check', [
            'pre_auth_token' => $preAuthToken,
            'code' => $code,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
    }

    public function test2faCheckFailsWithWrongCode(): void
    {
        $adminSecret = $_ENV['ADMIN_SECRET'] ?: '9575806574';

        $this->postJson('/api/admin/register', [
            'email' => 'admin2fa3@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'admin_secret' => $adminSecret,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin2fa3@test.com',
            'password' => 'Password1!',
        ]);

        $loginData = json_decode($this->client->getResponse()->getContent(), true);

        $this->postJson('/api/auth/2fa/check', [
            'pre_auth_token' => $loginData['pre_auth_token'],
            'code' => '000000',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test2faCheckFailsWithInvalidPreAuthToken(): void
    {
        $this->postJson('/api/auth/2fa/check', [
            'pre_auth_token' => 'invalid-token',
            'code' => '123456',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // --- 2FA setup / confirm (for users promoted to admin) ---

    private function createPendingAdmin(string $email = 'pending@test.com'): User
    {
        $totp = static::getContainer()->get(TotpAuthenticatorInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'Password1!'));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setTopSecret($totp->generateSecret());
        // twoFactorConfirmed stays false — pending setup
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testSetupReturnsQrCodeForUnconfirmedAdmin(): void
    {
        $user = $this->createPendingAdmin();
        $this->client->loginUser($user);

        $this->client->request('GET', '/api/auth/2fa/setup');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('totp_uri', $data);
        self::assertArrayHasKey('totp_secret', $data);
        self::assertStringStartsWith('otpauth://totp/', $data['totp_uri']);
    }

    public function testSetupReturnsConflictWhenAlreadyConfirmed(): void
    {
        $adminSecret = $_ENV['ADMIN_SECRET'] ?: '9575806574';
        $this->postJson('/api/admin/register', [
            'email' => 'confirmed@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'admin_secret' => $adminSecret,
        ]);

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'confirmed@test.com']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/api/auth/2fa/setup');

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testSetupRequiresAdminRole(): void
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail('regular@test.com');
        $user->setPassword($hasher->hashPassword($user, 'Password1!'));
        $this->em->persist($user);
        $this->em->flush();
        $this->client->loginUser($user);

        $this->client->request('GET', '/api/auth/2fa/setup');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testConfirmWithValidCodeSetsConfirmed(): void
    {
        $user = $this->createPendingAdmin('confirm_ok@test.com');
        $totpSecret = $user->getTopSecret();
        $this->client->loginUser($user);

        $code = TOTP::createFromSecret($totpSecret)->now();
        $this->postJson('/api/auth/2fa/confirm', ['code' => $code]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('2FA confirmed successfully', $data['message']);

        $this->em->refresh($user);
        self::assertTrue($user->isTwoFactorConfirmed());
        self::assertTrue($user->isTotpAuthenticationEnabled());
    }

    public function testConfirmWithInvalidCodeReturns401(): void
    {
        $user = $this->createPendingAdmin('confirm_fail@test.com');
        $this->client->loginUser($user);

        $this->postJson('/api/auth/2fa/confirm', ['code' => '000000']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $this->em->refresh($user);
        self::assertFalse($user->isTwoFactorConfirmed());
    }

    public function testUnconfirmedAdminReceivesJwtDirectlyOnLogin(): void
    {
        $this->createPendingAdmin('unconfirmed_login@test.com');

        $this->postJson('/api/auth/login', [
            'email' => 'unconfirmed_login@test.com',
            'password' => 'Password1!',
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data, 'Unconfirmed admin should get JWT directly, not a 2FA challenge');
        self::assertArrayNotHasKey('pre_auth_token', $data);
    }
}
