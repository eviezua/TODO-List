<?php

namespace App\Services;

use App\Entity\Status;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TaskService
{
    private $entityManager;
    private $taskRepository;

    public function __construct(EntityManagerInterface $entityManager, TaskRepository $taskRepository)
    {
        $this->entityManager = $entityManager;
        $this->taskRepository = $taskRepository;
    }
    public function findTasksByFilters(
        int $userId,
        ?string $status = null,
        ?int $priority = null,
        ?string $search = null,
        ?string $createdAt = null,
        ?string $completedAt = null,
        ?string $orderBy = null
    ) {
        return $this->taskRepository->findTasksByFilters($userId, $status, $priority, $search, $createdAt, $completedAt, $orderBy);
    }
    public function isTaskOwnedByUser(int $taskId, UserInterface $currentUser): bool
    {
        $task = $this->getTaskById($taskId);
        // Check if the task is owned by the given user
        return $task && $task->getOwner() === $currentUser;
    }
    public function getTaskById(int $taskId): ?Task
    {
        $task = $this->entityManager->find(Task::class, $taskId);

        return $task;
    }
    public function getTaskList(int $owner_id): array
    {
        $user = $this->entityManager->getRepository(User::class)->find($owner_id);
        $tasks = $user->getTasks()->toArray();

        return $tasks;
    }
    public function createTask(int $owner_id, string $title, string $description, int $priority): void
    {
        $user = $this->entityManager->getRepository(User::class)->find($owner_id);
        $task = new Task();
        $task->setOwner($user);
        $task->setStatus(Status::ToDo);
        $task->setPriority($priority);
        $task->setTitle($title);
        $task->setDescription($description);

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }
    public function updateTask(int $id, string $title, string $description, int $priority): void
    {
        $task = $this->getTaskById($id);
        if ($task) {
            $task->setPriority($priority);
            $task->setTitle($title);
            $task->setDescription($description);

            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
    }
    public function changeStatusTask(int $id): void
    {
        $task = $this->getTaskById($id);
        if ($task) {
            $task->setStatus(Status::Done);

            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
    }
    public function deleteTask(int $id): void
    {
        $task = $this->getTaskById($id);

        if ($task) {
            $this->entityManager->remove($task);
            $this->entityManager->flush();
        }
    }
}