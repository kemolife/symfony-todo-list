<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\TodoList;
use App\Entity\User;
use App\Enum\ApiKeyPermission;
use App\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, TodoList|null>
 */
final class TodoVoter extends Voter
{
    public const string READ = 'TODO_READ';
    public const string CREATE = 'TODO_CREATE';
    public const string EDIT = 'TODO_EDIT';
    public const string DELETE = 'TODO_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::READ, self::CREATE, self::EDIT, self::DELETE], true)) {
            return false;
        }

        // READ (list/tags) and CREATE operate on the collection — no subject
        if (in_array($attribute, [self::READ, self::CREATE], true) && null === $subject) {
            return true;
        }

        return $subject instanceof TodoList;
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

        // For API key tokens: enforce per-key permission before ownership
        if ($token instanceof ApiKeyToken) {
            $required = match ($attribute) {
                self::READ => ApiKeyPermission::Read,
                self::CREATE => ApiKeyPermission::Write,
                self::EDIT => ApiKeyPermission::Write,
                self::DELETE => ApiKeyPermission::Delete,
            };

            if (!$token->hasPermission($required)) {
                return false;
            }
        }

        // Collection-level operations (no TodoList subject) — permission check is sufficient
        if (null === $subject) {
            return true;
        }

        /** @var TodoList $subject */
        $owner = $subject->getOwner();

        if (null === $owner) {
            return false;
        }

        if (null !== $owner->getId() && null !== $user->getId()) {
            return $owner->getId() === $user->getId();
        }

        return $owner === $user;
    }
}
