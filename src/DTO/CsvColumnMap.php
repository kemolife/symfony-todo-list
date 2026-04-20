<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class CsvColumnMap
{
    public function __construct(
        public string $title = 'title',
        public string $description = 'description',
        public string $tag = 'tag',
        public string $status = 'status',
        public string $items = 'items',
    ) {
    }

    /** @param array<string, string> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? 'title',
            description: $data['description'] ?? 'description',
            tag: $data['tag'] ?? 'tag',
            status: $data['status'] ?? 'status',
            items: $data['items'] ?? 'items',
        );
    }

    public function getDtoProperty(string $csvHeader): ?string
    {
        if ($csvHeader === '') {
            return null;
        }

        return match (true) {
            $this->title !== '' && $csvHeader === $this->title             => 'title',
            $this->description !== '' && $csvHeader === $this->description => 'description',
            $this->tag !== '' && $csvHeader === $this->tag                 => 'tag',
            $this->status !== '' && $csvHeader === $this->status           => 'status',
            $this->items !== '' && $csvHeader === $this->items             => 'items',
            default                                                        => null,
        };
    }
}
