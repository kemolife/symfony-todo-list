<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\AuditLog;
use App\Entity\TodoItem;
use App\Entity\TodoList;
use App\Enum\AuditLogAction;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class AuditLogListener
{
    /** Entities whose changes are recorded */
    private const TRACKED = [TodoList::class, TodoItem::class];

    /** Fields to diff per entity class — add new classes here to extend coverage */
    private const TRACKED_FIELDS = [
        TodoList::class => ['name', 'description', 'tag', 'status'],
        TodoItem::class => ['title', 'isCompleted', 'position'],
    ];

    /** @var AuditLog[] */
    private array $pendingLogs = [];

    public function __construct(private readonly Security $security)
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!in_array($entity::class, self::TRACKED, true)) {
            return;
        }

        $this->pendingLogs[] = $this->buildLog($entity, AuditLogAction::Created, null);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!in_array($entity::class, self::TRACKED, true)) {
            return;
        }

        // Soft-delete: deletedAt goes from null → datetime
        if ($args->hasChangedField('deletedAt') && null !== $args->getNewValue('deletedAt')) {
            $this->pendingLogs[] = $this->buildLog($entity, AuditLogAction::Deleted, null);

            return;
        }

        $rawChangeset = [];
        foreach (self::TRACKED_FIELDS[$entity::class] as $field) {
            if ($args->hasChangedField($field)) {
                $rawChangeset[$field] = [
                    'from' => $args->getOldValue($field),
                    'to'   => $args->getNewValue($field),
                ];
            }
        }

        $changeset = $this->buildChangeset($entity::class, $rawChangeset);
        if (!empty($changeset)) {
            $this->pendingLogs[] = $this->buildLog($entity, AuditLogAction::Updated, $changeset);
        }
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!in_array($entity::class, self::TRACKED, true)) {
            return;
        }

        $this->pendingLogs[] = $this->buildLog($entity, AuditLogAction::Deleted, null);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (empty($this->pendingLogs)) {
            return;
        }

        $logs = $this->pendingLogs;
        $this->pendingLogs = [];

        $em = $args->getObjectManager();
        foreach ($logs as $log) {
            $em->persist($log);
        }
        $em->flush();
    }

    private function buildChangeset(string $entityClass, array $rawChangeset): array
    {
        $result = [];
        foreach (self::TRACKED_FIELDS[$entityClass] as $field) {
            if (!array_key_exists($field, $rawChangeset)) {
                continue;
            }
            ['from' => $from, 'to' => $to] = $rawChangeset[$field];
            $result[] = [
                'field' => $field,
                'from'  => $from instanceof \BackedEnum ? $from->value : $from,
                'to'    => $to instanceof \BackedEnum ? $to->value : $to,
            ];
        }

        return $result;
    }

    private function buildLog(object $entity, AuditLogAction $action, ?array $changes): AuditLog
    {
        $entityType = match ($entity::class) {
            TodoList::class => 'todo_list',
            TodoItem::class => 'todo_item',
        };

        $entityName = match (true) {
            $entity instanceof TodoList => $entity->getName(),
            $entity instanceof TodoItem => $entity->getTitle(),
        };

        return new AuditLog(
            entityType: $entityType,
            entityId: (int) $entity->getId(),
            entityName: $entityName,
            action: $action,
            changes: $changes,
            actorEmail: $this->security->getUser()?->getUserIdentifier() ?? 'system',
        );
    }
}
