<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\CsvColumnMap;
use App\DTO\Response\ImportResult;
use App\DTO\TodoImportDto;
use App\Entity\TodoItem;
use App\Entity\TodoList;
use App\Entity\User;
use App\Enum\TodoStatus;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CsvImportService
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function parser(mixed $file, User $user, CsvColumnMap $map = new CsvColumnMap()): ImportResult
    {
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new UnprocessableEntityHttpException('A valid CSV file is required');
        }

        $csv = Reader::from($file->openFile())
            ->setHeaderOffset(0);

        $renamedHeaders = array_map(
            fn(string $head) => $map->getDtoProperty($head) ?? $head,
            $csv->getHeader(),
        );

        $ownerId = $user->getId();
        $ownerRef = $this->em->getReference(User::class, $ownerId);
        $batch = 0;
        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($csv->getRecords($renamedHeaders) as $i => $record) {
            $dto = TodoImportDto::fromRecord($record);
            $violations = $this->validator->validate($dto);

            if (count($violations) > 0) {
                $failed++;
                foreach ($violations as $violation) {
                    $errors[] = sprintf('Row %d (%s): %s', $i + 1, $violation->getPropertyPath(), $violation->getMessage());
                }
                continue;
            }

            $todoList = (new TodoList())
                ->setName($dto->title)
                ->setDescription($dto->description ?: null)
                ->setTag($dto->tag ?: null)
                ->setStatus(TodoStatus::tryFrom($dto->status ?? '') ?? TodoStatus::Pending)
                ->setOwner($ownerRef);

            foreach ($dto->getItemTitles() as $position => $title) {
                $todoList->addTodoItem(
                    (new TodoItem())
                        ->setTitle($title)
                        ->setPosition($position)
                        ->setIsCompleted(false),
                );
            }

            $this->em->persist($todoList);
            $created++;

            $batch++;
            if ($batch % self::BATCH_SIZE === 0) {
                $this->em->flush();
                $this->em->clear();
                $ownerRef = $this->em->getReference(User::class, $ownerId);
            }
        }

        $this->em->flush();
        $this->em->clear();

        return new ImportResult($created, $failed, $errors);
    }
}
