<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Enum\ApiKeyPermission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProfileApiKeyControllerTest extends WebTestCase
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

    private function createApiKey(User $user, array $permissions = ['read', 'write', 'delete'], string $name = 'Test key'): ApiKey
    {
        $key = new ApiKey();
        $key->setUser($user);
        $key->setName($name);
        $key->setKeyValue(bin2hex(random_bytes(32)));
        $key->setPermissions(array_map(fn (string $v) => ApiKeyPermission::from($v), $permissions));
        $this->em->persist($key);
        $this->em->flush();

        return $key;
    }

    public function testListApiKeysReturnsEmpty(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/api/profile/api-keys');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame([], $data);
    }

    public function testCreateApiKeyReturnsKeyValueOnce(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('POST', '/api/profile/api-keys', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'CI key',
            'permissions' => ['read', 'write'],
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('keyValue', $data);
        self::assertSame(64, strlen($data['keyValue']));
        self::assertSame(8, strlen($data['prefix']));
        self::assertSame('CI key', $data['name']);
        self::assertSame(['read', 'write'], $data['permissions']);
        self::assertArrayHasKey('id', $data);

        // Subsequent list must NOT expose keyValue
        $this->client->request('GET', '/api/profile/api-keys');
        $list = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $list);
        self::assertNull($list[0]['keyValue']);
    }

    public function testCreateApiKeyValidationRejectsEmptyName(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('POST', '/api/profile/api-keys', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => '',
            'permissions' => ['read'],
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateApiKeyValidationRejectsInvalidPermission(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('POST', '/api/profile/api-keys', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Bad key',
            'permissions' => ['admin'],
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRevokeOwnKey(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);
        $key = $this->createApiKey($user);

        $this->client->request('DELETE', '/api/profile/api-keys/'.$key->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Key must be gone
        $this->client->request('GET', '/api/profile/api-keys');
        $list = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame([], $list);
    }

    public function testCannotRevokeAnotherUsersKey(): void
    {
        $owner = $this->createUser('owner@test.com');
        $attacker = $this->createUser('attacker@test.com');
        $key = $this->createApiKey($owner);

        $this->client->loginUser($attacker);
        $this->client->request('DELETE', '/api/profile/api-keys/'.$key->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAuthenticateWithReadOnlyApiKey(): void
    {
        $user = $this->createUser();
        $key = $this->createApiKey($user, ['read']);

        $this->client->request('GET', '/api/todos', [], [], ['HTTP_X_API_KEY' => $key->getKeyValue()]);

        self::assertResponseIsSuccessful();
    }

    public function testApiKeyWithoutCreatePermissionCannotCreateTodo(): void
    {
        $user = $this->createUser();
        $key = $this->createApiKey($user, ['read']);

        $this->client->request('POST', '/api/todos', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_KEY' => $key->getKeyValue(),
        ], json_encode(['name' => 'Test', 'status' => 'pending']));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testApiKeyWithAllPermissionsCanCreateTodo(): void
    {
        $user = $this->createUser();
        $key = $this->createApiKey($user, ['read', 'write', 'delete']);

        $this->client->request('POST', '/api/todos', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_KEY' => $key->getKeyValue(),
        ], json_encode(['name' => 'Via API key', 'status' => 'pending']));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testProfileShowReturnsApiKeyCount(): void
    {
        $user = $this->createUser();
        $this->createApiKey($user);
        $this->createApiKey($user, ['read'], 'Read-only');
        $this->em->refresh($user);
        $this->client->loginUser($user);

        $this->client->request('GET', '/api/profile');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame(2, $data['apiKeyCount']);
    }
}
