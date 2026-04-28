<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProfileControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createTestUser(string $email = 'profile@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('$2y$04$'.str_repeat('a', 53));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testShowProfileReturnsEmailAndNullName(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);
        $this->client->request('GET', '/api/profile');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame($user->getEmail(), $data['email']);
        self::assertNull($data['name']);
        self::assertArrayHasKey('apiKeyCount', $data);
    }

    public function testUpdateProfileName(): void
    {
        $user = $this->createTestUser('profile2@example.com');
        $this->client->loginUser($user);
        $this->client->request('PATCH', '/api/profile', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'John Doe']));

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('John Doe', $data['name']);
    }

    public function testChangePasswordWithWrongCurrentPasswordReturns422(): void
    {
        $user = $this->createTestUser('profile3@example.com');
        $this->client->loginUser($user);
        $this->client->request('PATCH', '/api/profile/password', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'currentPassword' => 'wrongpassword',
            'newPassword'     => 'newpassword123',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('Current password is incorrect.', $data['error']);
    }

    public function testChangePasswordValidationRequiresMinLength(): void
    {
        $user = $this->createTestUser('profile4@example.com');
        $this->client->loginUser($user);
        $this->client->request('PATCH', '/api/profile/password', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'currentPassword' => 'anything',
            'newPassword'     => 'short',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
