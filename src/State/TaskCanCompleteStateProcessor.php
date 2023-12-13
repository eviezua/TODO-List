<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Status;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('api_platform.doctrine.orm.state.persist_processor')]
class TaskCanCompleteStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProcessorInterface $innerProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (
            $data instanceof Task &&
            $data->getStatus() !== $context['request']->attributes->get('previous_data')
        ) {
            $data->setCanDelete($data->getStatus() !== Status::Done);
            $data->setCanComplete($data->getStatus() === Status::Done);

            $this->updateParent(
                $data->getStatus() !== Status::Done,
                $data->getStatus() === Status::Done,
                $data->getParent()
            );
        }

        $this->innerProcessor->process($data, $operation, $uriVariables, $context);
    }

    protected function updateParent(bool $canDelete, bool $canComplete, Task $entity = null): void
    {
        if (!$entity) {
            return;
        }

        $entity->setCanDelete($canDelete);
        $entity->setCanComplete($canComplete);

        $this->entityManager->persist($entity);

        $this->updateParent($canDelete, $canComplete, $entity->getParent());
    }
}
