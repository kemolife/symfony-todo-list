<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
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

    public function test_register_creates_user_and_returns_token(): void
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

    public function test_register_fails_with_invalid_email(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'not-an-email',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_register_fails_when_email_already_taken(): void
    {
        $this->createUser('existing@test.com', 'Password1!');

        $this->postJson('/api/auth/register', [
            'email' => 'existing@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function test_login_returns_token(): void
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

    public function test_login_fails_with_wrong_password(): void
    {
        $this->createUser('user@test.com', 'Password1!');

        $this->postJson('/api/auth/login', [
            'email' => 'user@test.com',
            'password' => 'WrongPassword1!',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function test_admin_login_returns_pre_auth_token(): void
    {
        $adminSecret = $_ENV['ADMIN_SECRET'] ?? '9575806574';

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

    public function test_2fa_check_returns_token_with_valid_code(): void
    {
        $adminSecret = $_ENV['ADMIN_SECRET'] ?? '9575806574';

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

    public function test_2fa_check_fails_with_wrong_code(): void
    {
        $adminSecret = $_ENV['ADMIN_SECRET'] ?? '9575806574';

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

    public function test_2fa_check_fails_with_invalid_pre_auth_token(): void
    {
        $this->postJson('/api/auth/2fa/check', [
            'pre_auth_token' => 'invalid-token',
            'code' => '123456',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
