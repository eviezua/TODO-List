<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Status;
use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('api_platform.doctrine.orm.state.persist_processor')]
class TaskCanCompleteStateProcessor implements ProcessorInterface
{
    public function __construct(
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager,
        private ProcessorInterface $innerProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (
            $data instanceof Task &&
            Status::Done !== $context['request']->attributes->get('previous_data') &&
            Status::Done === $data->getStatus()
        ) {
            $this->updateParent($data);
        }

        $this->innerProcessor->process($data, $operation, $uriVariables, $context);
    }

    protected function updateParent(Task $entity = null): void
    {
        $entity->setCanDelete(false);
        $entity->setCanComplete(true);

        $this->entityManager->persist($entity);

        if ($entity->getParent()) {
            $this->updateParent($entity->getParent());
        }
    }
}
