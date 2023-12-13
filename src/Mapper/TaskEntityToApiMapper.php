<?php

namespace App\Mapper;

use App\ApiResource\TaskApi;
use App\ApiResource\UserApi;
use App\Entity\Task;
use Symfonycasts\MicroMapper\AsMapper;
use Symfonycasts\MicroMapper\MapperInterface;
use Symfonycasts\MicroMapper\MicroMapperInterface;

#[AsMapper(from: Task::class, to: TaskApi::class)]
class TaskEntityToApiMapper implements MapperInterface
{
    public function __construct(
        private MicroMapperInterface $microMapper,
    ) {
    }

    public function load(object $from, string $toClass, array $context): object
    {
        $entity = $from;

        assert($entity instanceof Task);

        $dto = new TaskApi();
        $dto->id = $entity->getId();

        return $dto;
    }

    public function populate(object $from, object $to, array $context): object
    {
        $entity = $from;
        $dto = $to;

        assert($entity instanceof Task);
        assert($dto instanceof TaskApi);

        $dto->priority = $entity->getPriority();
        $dto->title = $entity->getTitle();
        $dto->description = $entity->getDescription();
        $dto->status = $entity->getStatus();
        $dto->owner = $this->microMapper->map($entity->getOwner(), UserApi::class, [
            MicroMapperInterface::MAX_DEPTH => 0,
        ]);

        if ($entity->getParent()) {
            $dto->parent = $this->microMapper->map($entity->getParent(), TaskApi::class, [
                MicroMapperInterface::MAX_DEPTH => 0,
            ]);
        }
        $dto->canDelete = $entity->isCanDelete();

        return $dto;
    }
}