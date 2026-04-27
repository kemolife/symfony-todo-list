<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TodoImportDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\Length(max: 65535)]
    public ?string $description = null;

    #[Assert\Length(max: 100)]
    public ?string $tag = null;

    public ?string $status = null;

    public ?string $items = null;

    /** @param array<string, string|null> $record */
    public static function fromRecord(array $record): self
    {
        $dto = new self();
        $dto->title = $record['title'] ?? null;
        $dto->description = $record['description'] ?? null;
        $dto->tag = $record['tag'] ?? null;
        $dto->status = $record['status'] ?? null;
        $dto->items = $record['items'] ?? null;

        return $dto;
    }

    /** @return string[] */
    public function getItemTitles(): array
    {
        if (null === $this->items || '' === $this->items) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode('|', $this->items))));
    }
}
