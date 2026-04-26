<?php

declare(strict_types=1);

namespace App\Command;

use App\DTO\Request\TodoRequest;
use App\Enum\TodoStatus;
use App\Service\TodoService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-todo',
    description: 'Create todo item',
    help: 'This command allows you to create a todo list item',
    usages: ['app:create-todo "Buy milk" --tag=shopping --status=pending'],
)]
class CreateTodoCommand
{
    public function __construct(private TodoService $todoService)
    {
    }

    public function __invoke(
        #[Argument('Name for your todo item')] string $name,
        OutputInterface $output,
        #[Option(name: 'description', shortcut: 'd', description: 'Todo description')] ?string $description = null,
        #[Option(name: 'tag', shortcut: 't', description: 'Todo tag')] ?string $tag = null,
        #[Option(name: 'status', shortcut: 's', description: 'Todo status (pending, in_progress, done)', suggestedValues: ['pending', 'in_progress', 'done'])] ?string $status = null,
    ): int {
        $todoStatus = null;
        if (null !== $status) {
            $todoStatus = TodoStatus::tryFrom($status);
            if (null === $todoStatus) {
                $validStatuses = implode(', ', array_column(TodoStatus::cases(), 'value'));
                $output->writeln(sprintf('<error>Invalid status "%s". Valid values: %s</error>', $status, $validStatuses));

                return Command::FAILURE;
            }
        }

        $output->writeln([
            'Todo Creator',
            '============',
            '',
        ]);

        $dto = new TodoRequest();
        $dto->name = $name;
        $dto->description = $description;
        $dto->tag = $tag;
        $dto->status = $todoStatus;

        $todo = $this->todoService->create($dto);

        $output->writeln(sprintf('Created todo #%d: %s', $todo->id, $todo->name));

        return Command::SUCCESS;
    }
}
