<?php

namespace App\Controller;

use App\Entity\Status;
use App\Entity\Task;
use App\Services\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
class TaskController extends AbstractController
{
    private $taskService;
    private $em;
    public function __construct(TaskService $taskService, EntityManagerInterface $em)
    {
        $this->taskService = $taskService;
        $this->em = $em;
    }
    #[Route('/', name: 'index')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your task controller!',
            'path' => 'src/Controller/TaskController.php',
        ]);
    }
    /*#[Route('/user', name: 'task.show', methods: 'GET')]
    public function getAccount(#[CurrentUser] UserInterface $user): JsonResponse
    {
        return $this->json($user);
    }*/
    #[Route('user/{id}/tasks', name: 'task.show', methods: 'GET')]
    public function getTasks(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');
        $search = $request->query->get('search');
        $createdAt= $request->query->get('createdAt');
        $completedAt= $request->query->get('completedAt');
        $orderBy = $request->query->get('orderBy');
        if ($currentUser->getId() !== $id) {
            return $this->json([
                'message' => 'Access denied. You are not the owner of this tasks.',
            ]);
        }
        $tasks = $this->taskService->findTasksByFilters($id, $status, (int)$priority, $search, $createdAt, $completedAt, $orderBy);
        if (empty($tasks)) {
            return new JsonResponse(['error' => 'Tasks not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->json(['tasks' => $tasks]);
    }
    #[Route('user/{id}/tasks/create', name: 'task.create', methods: 'GET')]
    public function createTasks(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if ($currentUser->getId() !== $id) {
            return $this->json([
                'message' => 'Access denied. You are not allowed to create tasks for this user.',
            ]);
        }
        $title = $request->query->get('title');
        $description = $request->query->get('description');
        $priority = $request->query->get('priority');
        $priority = (int)$priority;
        if ($priority > 5 || $priority < 1) {
            return $this->json(['error' => 'Priority must be between 1 and 5.']);
        }
        if (!$title || !$priority) {
            return $this->json(['error' => 'Incomplete data. Please provide title, description, and priority.'], JsonResponse::HTTP_BAD_REQUEST);
        }
        if ($description == null) {
            $description = '';
        }
        try {
            $this->taskService->createTask($currentUser->getId(), $title, $description, $priority);
            return $this->json(['message' => 'Task created successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('user/{id}/tasks/{taskId}/complete', name: 'task.complete', methods: 'GET')]
    public function completeTasks(int $taskId): JsonResponse
    {
        $currentUser = $this->getUser();
        if ($this->taskService->isTaskOwnedByUser($taskId, $currentUser) === true){
            $this->taskService->changeStatusTask($taskId);
            return $this->json(['message' => 'Task completed successfully!']);
        }
        if ($this->taskService->isTaskOwnedByUser($taskId, $currentUser) === false){
            if (!$this->taskService->getTaskById($taskId)){
                return $this->json(['error' => 'Task not found.']);
            }
            return $this->json(['error' => 'Access denied. You are not allowed to complete tasks for this user.']);
        }
        return $this->json(['message' => 'Cannot complete the task.']);
    }
    #[Route('user/{id}/tasks/{taskId}/edit', name: 'task.edit')]
    public function editTasks(int $taskId, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if ($this->taskService->isTaskOwnedByUser($taskId, $currentUser) === true){
            $title = $request->query->get('title');
            $description = $request->query->get('description');
            $priority = $request->query->get('priority');
            $priority = (int)$priority;
            if (!$title || !$priority) {
                return $this->json(['error' => 'Incomplete data. Please provide title, description, and priority.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            if ($priority > 5 || $priority < 1) {
                return $this->json(['error' => 'Priority must be between 1 and 5.']);
            }
            if ($description == null) {
                $description = '';
            }
            $this->taskService->updateTask($taskId, $title, $description, $priority);
            return $this->json(['message' => 'Task updated successfully!']);
        }
        if ($this->taskService->isTaskOwnedByUser($taskId, $currentUser) === false){
            if (!$this->taskService->getTaskById($taskId)){
                return $this->json(['error' => 'Task not found.']);
            }
            return $this->json(['error' => 'Access denied. You are not allowed to edit tasks for this user.']);
        }
        return $this->json(['message' => 'Cannot edit the task.']);
    }
    #[Route('user/{id}/tasks/{taskId}/delete', name: 'task.delete', methods: ['DELETE', 'GET'])]
    public function deleteTask(int $id, int $taskId): JsonResponse
    {
        $currentUser = $this->getUser();
        if ($this->taskService->isTaskOwnedByUser($taskId, $currentUser) === false){
            if (!$this->taskService->getTaskById($taskId)){
                return $this->json(['error' => 'Task not found.']);
            }
            return $this->json(['error' => 'Access denied. You are not allowed to edit tasks for this user.']);
        }
        if ($this->taskService->getTaskById($taskId)->getStatus() === Status::Done){
            return $this->json(['error' => 'Cannot delete done task.']);
        }
        $this->taskService->deleteTask($taskId);
        return $this->json(['message' => 'Task deleted successfully!']);
    }
}
