<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;
    private string $adminSecret;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->adminSecret = $_ENV['ADMIN_SECRET'] ?? '9575806574';
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'admin@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'admin_secret' => $this->adminSecret,
        ], $overrides);
    }

    public function test_create_admin_returns_token_and_totp_data(): void
    {
        $this->postJson('/api/admin/register', $this->validPayload());

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
        self::assertArrayHasKey('totp_secret', $data);
        self::assertArrayHasKey('totp_uri', $data);
        self::assertNotEmpty($data['token']);
        self::assertNotEmpty($data['totp_secret']);
        self::assertStringStartsWith('otpauth://totp/', $data['totp_uri']);
    }

    public function test_create_admin_fails_with_wrong_secret(): void
    {
        $this->postJson('/api/admin/register', $this->validPayload(['admin_secret' => 'wrong-secret']));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function test_create_admin_fails_with_duplicate_email(): void
    {
        $this->postJson('/api/admin/register', $this->validPayload());
        $this->postJson('/api/admin/register', $this->validPayload());

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function test_create_admin_fails_with_weak_password(): void
    {
        $this->postJson('/api/admin/register', $this->validPayload([
            'password' => 'weakpassword',
            'password_confirmation' => 'weakpassword',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_created_user_has_admin_role(): void
    {
        $this->postJson('/api/admin/register', $this->validPayload());

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
        self::assertNotNull($user);
        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }
}
