<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(nullable: true)]
    private ?string $topSecret = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $twoFactorConfirmed = false;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $enrollmentToken = null;

    #[ORM\Column(length: 36, nullable: true, unique: true)]
    private ?string $apiKey = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $enrollmentTokenExpiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = UserRole::User->value;

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getTopSecret(): ?string
    {
        return $this->topSecret;
    }

    public function setTopSecret(?string $topSecret): static
    {
        $this->topSecret = $topSecret;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function hasRole(UserRole $role): bool
    {
        return in_array($role->value, $this->getRoles(), true);
    }

    public function isTwoFactorConfirmed(): bool
    {
        return $this->twoFactorConfirmed;
    }

    public function setTwoFactorConfirmed(bool $twoFactorConfirmed): static
    {
        $this->twoFactorConfirmed = $twoFactorConfirmed;

        return $this;
    }

    public function getEnrollmentToken(): ?string
    {
        return $this->enrollmentToken;
    }

    public function setEnrollmentToken(?string $enrollmentToken): static
    {
        $this->enrollmentToken = $enrollmentToken;

        return $this;
    }

    public function getEnrollmentTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->enrollmentTokenExpiresAt;
    }

    public function setEnrollmentTokenExpiresAt(?\DateTimeImmutable $enrollmentTokenExpiresAt): static
    {
        $this->enrollmentTokenExpiresAt = $enrollmentTokenExpiresAt;

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return null !== $this->topSecret && $this->twoFactorConfirmed;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return (string) $this->email;
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        if (null === $this->topSecret) {
            return null;
        }

        return new TotpConfiguration($this->topSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }
}
