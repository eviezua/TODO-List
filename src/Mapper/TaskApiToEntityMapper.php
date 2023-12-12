<?php

namespace App\Mapper;

use App\ApiResource\TaskApi;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;
use Symfonycasts\MicroMapper\MicroMapperInterface;

#[AsMapper(from: TaskApi::class, to: Task::class)]
class TaskApiToEntityMapper implements MapperInterface
{
    public function __construct(
        private TaskRepository $userRepository,
        private Security $security,
        private MicroMapperInterface $microMapper,
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        $dto = $from;

        assert($dto instanceof TaskApi);

        $entity = $dto->id ? $this->userRepository->find($dto->id) : new Task();
        if (!$entity) {
            throw new \Exception('Task not found');
        }

        return $entity;
    }

    public function populate(object $from, object $to, array $context): object
    {
        $dto = $from;
        $entity = $to;

        assert($dto instanceof TaskApi);
        assert($entity instanceof Task);

        if ($dto->owner) {
            $entity->setOwner($this->microMapper->map($dto->owner, User::class, [
                MicroMapperInterface::MAX_DEPTH => 0,
            ]));
        } else {
            $entity->setOwner($this->security->getUser());
        }

        $entity->setPriority($dto->priority);
        $entity->setTitle($dto->title);
        $entity->setDescription($dto->description);
        $entity->setStatus($dto->status);

        return $entity;
    }
}