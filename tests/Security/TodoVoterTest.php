<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\ToDoList;
use App\Entity\User;
use App\Security\TodoVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class TodoVoterTest extends TestCase
{
    private TodoVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TodoVoter();
    }

    private function makeUser(?string $role = null): User
    {
        $user = new User();
        $user->setEmail(uniqid().'@test.com');
        if ($role) {
            $user->setRoles([$role]);
        }

        return $user;
    }

    private function makeTodo(?User $owner = null): ToDoList
    {
        $todo = new ToDoList();
        if ($owner) {
            $todo->setOwner($owner);
        }

        return $todo;
    }

    private function token(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }

    public function testOwnerCanEdit(): void
    {
        $owner = $this->makeUser();
        $todo = $this->makeTodo($owner);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token($owner), $todo, [TodoVoter::EDIT])
        );
    }

    public function testOwnerCanDelete(): void
    {
        $owner = $this->makeUser();
        $todo = $this->makeTodo($owner);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token($owner), $todo, [TodoVoter::DELETE])
        );
    }

    public function testNonOwnerCannotEdit(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $todo = $this->makeTodo($owner);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token($other), $todo, [TodoVoter::EDIT])
        );
    }

    public function testNonOwnerCannotDelete(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $todo = $this->makeTodo($owner);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token($other), $todo, [TodoVoter::DELETE])
        );
    }

    public function testAdminCanEditAnyTodo(): void
    {
        $admin = $this->makeUser('ROLE_ADMIN');
        $owner = $this->makeUser();
        $todo = $this->makeTodo($owner);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token($admin), $todo, [TodoVoter::EDIT])
        );
    }

    public function testAdminCanDeleteAnyTodo(): void
    {
        $admin = $this->makeUser('ROLE_ADMIN');
        $owner = $this->makeUser();
        $todo = $this->makeTodo($owner);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token($admin), $todo, [TodoVoter::DELETE])
        );
    }

    public function testUnsupportedAttributeAbstains(): void
    {
        $user = $this->makeUser();
        $todo = $this->makeTodo($user);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token($user), $todo, ['UNSUPPORTED'])
        );
    }

    public function testUnsupportedSubjectAbstains(): void
    {
        $user = $this->makeUser();

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token($user), new \stdClass(), [TodoVoter::EDIT])
        );
    }
}
