<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\TodoList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateTodoCommandTest extends KernelTestCase
{
    private CommandTester $tester;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:create-todo');
        $this->tester = new CommandTester($command);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateWithNameOnly(): void
    {
        $this->tester->execute(['name' => 'Buy milk']);

        self::assertSame(0, $this->tester->getStatusCode());
        self::assertStringContainsString('Created todo', $this->tester->getDisplay());

        $todo = $this->em->getRepository(TodoList::class)->findOneBy(['name' => 'Buy milk']);
        self::assertNotNull($todo);
        self::assertSame('pending', $todo->getStatus()->value);
    }

    public function testCreateWithAllOptions(): void
    {
        $this->tester->execute([
            'name' => 'Write tests',
            '--description' => 'Add PHPUnit tests',
            '--tag' => 'dev',
            '--status' => 'in_progress',
        ]);

        self::assertSame(0, $this->tester->getStatusCode());

        $todo = $this->em->getRepository(TodoList::class)->findOneBy(['name' => 'Write tests']);
        self::assertNotNull($todo);
        self::assertSame('in_progress', $todo->getStatus()->value);
        self::assertSame('dev', $todo->getTag());
        self::assertSame('Add PHPUnit tests', $todo->getDescription());
    }

    public function testInvalidStatusReturnsFailure(): void
    {
        $this->tester->execute([
            'name' => 'Bad status todo',
            '--status' => 'invalid_value',
        ]);

        self::assertSame(1, $this->tester->getStatusCode());
        self::assertStringContainsString('Invalid status', $this->tester->getDisplay());
    }
}
