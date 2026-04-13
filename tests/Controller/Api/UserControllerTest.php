<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class UserControllerTest extends WebTestCase
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

    private function putJson(string $uri, array $body): void
    {
        $this->client->request(
            'PUT',
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($body),
        );
    }

    public function testPromoteUserToAdminEnables2FA(): void
    {
        $admin = $this->createAdmin('admin@test.com');
        $user = $this->createUser('user@test.com');
        $this->client->loginUser($admin);

        $this->putJson('/api/users/'.$user->getId(), [
            'email' => 'user@test.com',
            'role' => 'admin',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertNotNull($data['totpUri']);
        self::assertNotNull($data['totpSecret']);
        self::assertStringStartsWith('otpauth://totp/', $data['totpUri']);
        self::assertContains('ROLE_ADMIN', $data['roles']);
        self::assertFalse($data['hasTwoFactor'], 'Promoted user should not have 2FA active until confirmed');

        $this->em->refresh($user);
        self::assertContains('ROLE_ADMIN', $user->getRoles());
        self::assertNotNull($user->getTopSecret());
        self::assertFalse($user->isTwoFactorConfirmed());
    }

    public function testDemoteAdminToUserDisables2FA(): void
    {
        $admin = $this->createAdmin('admin@test.com');
        $targetAdmin = $this->createAdmin('target@test.com');
        $this->client->loginUser($admin);

        $this->putJson('/api/users/'.$targetAdmin->getId(), [
            'email' => 'target@test.com',
            'role' => 'user',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertNull($data['totpUri']);
        self::assertNotContains('ROLE_ADMIN', $data['roles']);

        $this->em->refresh($targetAdmin);
        self::assertNotContains('ROLE_ADMIN', $targetAdmin->getRoles());
        self::assertNull($targetAdmin->getTopSecret());
    }

    public function testUpdateWithoutRoleDoesNotChangeRole(): void
    {
        $admin = $this->createAdmin('admin@test.com');
        $targetAdmin = $this->createAdmin('target@test.com');
        $this->client->loginUser($admin);

        $this->putJson('/api/users/'.$targetAdmin->getId(), [
            'email' => 'target@test.com',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->em->refresh($targetAdmin);
        self::assertContains('ROLE_ADMIN', $targetAdmin->getRoles());
        self::assertNotNull($targetAdmin->getTopSecret());
    }

    public function testUpdateRequiresAdminRole(): void
    {
        $user = $this->createUser('user@test.com');
        $this->client->loginUser($user);

        $this->putJson('/api/users/'.$user->getId(), [
            'email' => 'user@test.com',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testInvalidRoleValueIsRejected(): void
    {
        $admin = $this->createAdmin('admin@test.com');
        $user = $this->createUser('user@test.com');
        $this->client->loginUser($admin);

        $this->putJson('/api/users/'.$user->getId(), [
            'email' => 'user@test.com',
            'role' => 'superadmin',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
