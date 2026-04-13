<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\ToDoList;
use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, ToDoList>
 */
final class TodoVoter extends Voter
{
    public const string EDIT = 'TODO_EDIT';
    public const string DELETE = 'TODO_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true)
            && $subject instanceof ToDoList;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (in_array(UserRole::Admin->value, $token->getRoleNames(), true)) {
            return true;
        }

        $owner = $subject->getOwner();

        if (null === $owner) {
            return false;
        }

        // Compare by ID when persisted, fall back to object identity for transient entities
        if (null !== $owner->getId() && null !== $user->getId()) {
            return $owner->getId() === $user->getId();
        }

        return $owner === $user;
    }
}
