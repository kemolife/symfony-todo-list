<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Enum\ApiKeyPermission;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AdminApiKeyControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createUser(string $email = 'user@test.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('$2y$04$'.str_repeat('a', 53));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createAdmin(string $email = 'admin@test.com'): User
    {
        $totp = static::getContainer()->get(TotpAuthenticatorInterface::class);
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('$2y$04$'.str_repeat('a', 53));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setTopSecret($totp->generateSecret());
        $user->setTwoFactorConfirmed(true);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createApiKey(User $user, string $name = 'Test key'): ApiKey
    {
        $key = new ApiKey();
        $key->setUser($user);
        $key->setName($name);
        $key->setKeyValue(bin2hex(random_bytes(32)));
        $key->setPermissions([ApiKeyPermission::Read, ApiKeyPermission::Write]);
        $this->em->persist($key);
        $this->em->flush();

        return $key;
    }

    public function testAdminCanListKeysForUser(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $this->createApiKey($user, 'Key A');
        $this->createApiKey($user, 'Key B');

        $this->client->loginUser($admin);
        $this->client->request('GET', '/api/admin/users/'.$user->getId().'/api-keys');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(2, $data);
        self::assertNull($data[0]['keyValue']);
    }

    public function testAdminCanRevokeAnyKey(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $key = $this->createApiKey($user);

        $this->client->loginUser($admin);
        $this->client->request('DELETE', '/api/admin/api-keys/'.$key->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify it's gone
        $this->client->request('GET', '/api/admin/users/'.$user->getId().'/api-keys');
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame([], $data);
    }

    public function testNonAdminCannotAccessAdminEndpoints(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/api/admin/users/'.$user->getId().'/api-keys');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminListReturnsEmptyForUserWithNoKeys(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->client->loginUser($admin);
        $this->client->request('GET', '/api/admin/users/'.$user->getId().'/api-keys');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame([], $data);
    }
}
