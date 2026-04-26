<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ApiKeyPermission;
use App\Repository\ApiKeyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[ORM\Table(name: 'api_key')]
class ApiKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'apiKeys')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'key_value', length: 64, unique: true)]
    private string $keyValue;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json')]
    private array $permissions = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getKeyValue(): string
    {
        return $this->keyValue;
    }

    public function setKeyValue(string $keyValue): static
    {
        $this->keyValue = $keyValue;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return ApiKeyPermission[]
     */
    public function getPermissions(): array
    {
        return array_map(
            fn (string $v) => ApiKeyPermission::from($v),
            $this->permissions,
        );
    }

    /**
     * @param ApiKeyPermission[] $permissions
     */
    public function setPermissions(array $permissions): static
    {
        $this->permissions = array_map(fn (ApiKeyPermission $p) => $p->value, $permissions);

        return $this;
    }

    public function hasPermission(ApiKeyPermission $permission): bool
    {
        return in_array($permission->value, $this->permissions, true);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }
}
