<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Status;
use App\Entity\Task;
use App\Entity\User;
use App\Factory\TaskFactory;
use Zenstruck\Foundry\Test\ResetDatabase;

class TaskTest extends ApiTestCase
{
    use ResetDatabase;

    public function testCreateTask()
    {
        $user = $this->createUser('test@example.com', 'password');
        $userId = $user->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('POST', '/api/tasks', [
            'auth_bearer' => $token,
            'json' => [
                "owner" => "/api/users/{$userId}",
                'status' => Status::ToDo,
                'priority' => 1,
                'title' => 'Test Task',
                'description' => 'test',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testCreateTaskWithoutTitle()
    {
        $user = $this->createUser('test@example.com', 'password');
        $userId = $user->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('POST', '/api/tasks', [
            'auth_bearer' => $token,
            'json' => [
                "owner" => "/api/users/{$userId}",
                'status' => Status::ToDo,
                'priority' => 1,
                'description' => 'test',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(
            [
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'title: This value should not be blank.',
                'title' => 'An error occurred',
            ]
        );
    }

    /**
     * @dataProvider invalidPriority
     */
    public function testCreateTaskWithInvalidPriority($priority)
    {
        $user = $this->createUser('test@example.com', 'password');
        $userId = $user->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('POST', '/api/tasks', [
            'auth_bearer' => $token,
            'json' => [
                "owner" => "/api/users/{$userId}",
                'status' => Status::ToDo,
                'title' => 'Test Task',
                'priority' => $priority,
                'description' => 'test',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(
            [
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'priority: This value should be between 1 and 5.',
                'title' => 'An error occurred',
            ]
        );
    }

    public function invalidPriority(): array
    {
        return [
            [9,],
            [0,],
            [-1,],
        ];
    }

    public function testCreateTaskWithoutPriority()
    {
        $user = $this->createUser('test@example.com', 'password');
        $userId = $user->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('POST', '/api/tasks', [
            'auth_bearer' => $token,
            'json' => [
                "owner" => "/api/users/{$userId}",
                'status' => Status::ToDo,
                'title' => 'Test Task',
                'description' => 'test',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(
            [
                'hydra:title' => 'An error occurred',
                'hydra:description' => 'priority: This value should not be blank.',
                'title' => 'An error occurred',
            ]
        );
    }

    public function testCreateTaskWithSubTask()
    {
        $user = $this->createUser('test@example.com', 'password');
        $userId = $user->getId();
        $task = TaskFactory::createOne(['owner' => $user]);
        $taskId = $task->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('POST', '/api/tasks', [
            'auth_bearer' => $token,
            'json' => [
                "owner" => "/api/users/{$userId}",
                'parent' => "/api/tasks/{$taskId}",
                'status' => Status::ToDo,
                'priority' => 1,
                'title' => 'Test Task',
                'description' => 'test',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(
            Task::class,
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['parent' => $taskId]
            )
        );
    }

    public function testGetCollection()
    {
        $user = $this->createUser('test@example.com', 'password');
        TaskFactory::createMany(100, ['owner' => $user]);
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('GET', '/api/tasks', ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => '/api/tasks',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/api/tasks?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/api/tasks?page=1',
                'hydra:last' => '/api/tasks?page=4',
                'hydra:next' => '/api/tasks?page=2',
            ],
        ]);
    }

    public function testGetCollectionReturnOwnerTasks()
    {
        $user = $this->createUser('test@example.com', 'password');
        TaskFactory::createMany(100, ['owner' => $user]);

        $anotherUser = $this->createUser('another@example.com', 'password');
        TaskFactory::createMany(10, ['owner' => $anotherUser]);

        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('GET', '/api/tasks', ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => '/api/tasks',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/api/tasks?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/api/tasks?page=1',
                'hydra:last' => '/api/tasks?page=4',
                'hydra:next' => '/api/tasks?page=2',
            ],
        ]);
    }

    public function testGetCollectionFilterByStatus()
    {
        $user = $this->createUser('test@example.com', 'password');
        TaskFactory::createMany(100, ['owner' => $user, 'status' => Status::ToDo]);
        TaskFactory::createMany(10, ['owner' => $user, 'status' => Status::Done]);
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('GET', '/api/tasks?status=todo', ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => '/api/tasks',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/api/tasks?status=todo&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/api/tasks?status=todo&page=1',
                'hydra:last' => '/api/tasks?status=todo&page=4',
                'hydra:next' => '/api/tasks?status=todo&page=2',
            ],
        ]);
    }

    public function testGetCollectionFilterByPriority()
    {
        $user = $this->createUser('test@example.com', 'password');
        TaskFactory::createMany(100, ['owner' => $user, 'priority' => 5]);
        TaskFactory::createMany(10, ['owner' => $user, 'priority' => 1]);
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('GET', '/api/tasks?priority=1', ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => '/api/tasks',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 10,
            'hydra:view' => [
                '@id' => '/api/tasks?priority=1',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);
    }

    public function testGetCollectionFilterByTitle()
    {
        $user = $this->createUser('test@example.com', 'password');
        TaskFactory::createMany(1, ['owner' => $user, 'title' => 'another title']);

        TaskFactory::createMany(1, ['owner' => $user, 'title' => 'test task']);
        TaskFactory::createMany(1, ['owner' => $user, 'title' => 'foobar test task']);
        TaskFactory::createMany(1, ['owner' => $user, 'title' => 'task test']);

        TaskFactory::createMany(1, ['owner' => $user, 'title' => 'foobar TEST task']);

        TaskFactory::createMany(1, ['owner' => $user, 'title' => 'foobarTest']);
        TaskFactory::createMany(1, ['owner' => $user, 'title' => 'foobarTestTask']);
        TaskFactory::createMany(1, ['owner' => $user, 'title' => 'TestTask']);

        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('GET', '/api/tasks?title=test', ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => '/api/tasks',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 7,
            'hydra:view' => [
                '@id' => '/api/tasks?title=test',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);
    }

    public function testGetCollectionFilterByDescription()
    {
        $user = $this->createUser('test@example.com', 'password');
        TaskFactory::createMany(1, ['owner' => $user, 'description' => 'another description']);

        TaskFactory::createMany(1, ['owner' => $user, 'description' => 'test task']);
        TaskFactory::createMany(1, ['owner' => $user, 'description' => 'foobar test task']);
        TaskFactory::createMany(1, ['owner' => $user, 'description' => 'task test']);

        TaskFactory::createMany(1, ['owner' => $user, 'description' => 'foobar TEST task']);

        TaskFactory::createMany(1, ['owner' => $user, 'description' => 'foobarTest']);
        TaskFactory::createMany(1, ['owner' => $user, 'description' => 'foobarTestTask']);
        TaskFactory::createMany(1, ['owner' => $user, 'description' => 'TestTask']);

        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('GET', '/api/tasks?description=test', ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => '/api/tasks',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 7,
            'hydra:view' => [
                '@id' => '/api/tasks?description=test',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);
    }

    public function testGetCollectionSortByPriority()
    {
        $user = $this->createUser('test@example.com', 'password');
        TaskFactory::createMany(1, ['owner' => $user, 'priority' => 1]);
        TaskFactory::createMany(1, ['owner' => $user, 'priority' => 2]);
        $token = $this->getToken('test@example.com', 'password');

        $response = static::createClient()->request('GET', '/api/tasks?order[priority]=desc', ['auth_bearer' => $token]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => '/api/tasks',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
            'hydra:view' => [
                '@id' => '/api/tasks?order%5Bpriority%5D=desc',
                '@type' => 'hydra:PartialCollectionView',
            ]
        ]);

        $collection = json_decode($response->getContent(), true)['hydra:member'];
        $this->assertEquals(2, $collection[0]['priority']);
        $this->assertEquals(1, $collection[1]['priority']);
    }

    public function testGetCollectionSortByCompletedAt()
    {
        $user = $this->createUser('test@example.com', 'password');
        TaskFactory::createMany(
            1,
            ['owner' => $user, 'completedAt' => new \DateTimeImmutable('- 1 day'), 'title' => 'task1']
        );
        TaskFactory::createMany(1, ['owner' => $user, 'completedAt' => new \DateTimeImmutable(), 'title' => 'task2']);
        $token = $this->getToken('test@example.com', 'password');

        $response = static::createClient()->request(
            'GET', '/api/tasks?order[completedAt]=desc',
            ['auth_bearer' => $token]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => '/api/tasks',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
            'hydra:view' => [
                '@id' => '/api/tasks?order%5BcompletedAt%5D=desc',
                '@type' => 'hydra:PartialCollectionView',
            ]
        ]);

        $collection = json_decode($response->getContent(), true)['hydra:member'];
        $this->assertEquals('task2', $collection[0]['title']);
        $this->assertEquals('task1', $collection[1]['title']);
    }

    public function testGetCollectionSortByCreatedAt()
    {
        $user = $this->createUser('test@example.com', 'password');
        TaskFactory::new()->createOne([
            'createdAt' => new \DateTimeImmutable('-1 day'),
            'owner' => $user,
            'title' => 'task1'
        ]);
        TaskFactory::new()->createOne([
            'owner' => $user,
            'title' => 'task2'
        ]);
        $token = $this->getToken('test@example.com', 'password');

        $response = static::createClient()->request('GET', '/api/tasks?order[createdAt]=desc', ['auth_bearer' => $token]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => '/api/tasks',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
            'hydra:view' => [
                '@id' => '/api/tasks?order%5BcreatedAt%5D=desc',
                '@type' => 'hydra:PartialCollectionView',
            ]
        ]);

        $collection = json_decode($response->getContent(), true)['hydra:member'];
        $this->assertEquals('task2', $collection[0]['title']);
        $this->assertEquals('task1', $collection[1]['title']);
    }

    public function testGetCollectionSortByCreatedAtAndPriority()
    {
        $user = $this->createUser('test@example.com', 'password');
        TaskFactory::new()->createOne([
            'createdAt' => new \DateTimeImmutable('-1 day'),
            'priority' => 1,
            'owner' => $user,
            'title' => 'task1'
        ]);
        TaskFactory::new()->createOne([
            'priority' => 2,
            'owner' => $user,
            'title' => 'task2'
        ]);
        $token = $this->getToken('test@example.com', 'password');

        $response = static::createClient()->request(
            'GET', '/api/tasks?order[createdAt]=desc&order[priority]=asc',
            ['auth_bearer' => $token]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => '/api/tasks',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
            'hydra:view' => [
                '@id' => '/api/tasks?order%5BcreatedAt%5D=desc&order%5Bpriority%5D=asc',
                '@type' => 'hydra:PartialCollectionView',
            ]
        ]);

        $collection = json_decode($response->getContent(), true)['hydra:member'];
        $this->assertEquals('task2', $collection[0]['title']);
        $this->assertEquals('task1', $collection[1]['title']);
    }

    public function testGetTask()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user]);
        $taskId = $task->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('GET', "/api/tasks/$taskId", ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => "/api/tasks/$taskId",
            '@type' => 'Task',
        ]);
    }

    public function testDeleteTask()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo]);
        $taskId = $task->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('DELETE', "/api/tasks/$taskId", ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['id' => $taskId]
            )
        );
    }

    public function testDeleteTaskOfAnotherOwnerForbidden()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo]);
        $user1 = $this->createUser('test1@example.com', 'password');

        $taskId = $task->getId();
        $token = $this->getToken('test1@example.com', 'password');

        static::createClient()->request('DELETE', "/api/tasks/$taskId", ['auth_bearer' => $token]);

        $this->assertResponseStatusCodeSame(403);

        $this->assertInstanceOf(
            Task::class,
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['id' => $taskId]
            )
        );
    }

    public function testDeleteCompletedTaskForbidden()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::Done]);

        $taskId = $task->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('DELETE', "/api/tasks/$taskId", [
            'auth_bearer' => $token
        ]);

        $this->assertResponseStatusCodeSame(403);

        $this->assertInstanceOf(
            Task::class,
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['id' => $taskId]
            )
        );
    }

    public function testDeleteTaskWithSubTasks()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo]);
        $subtask = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo, 'parent' => $task]);

        $taskId = $task->getId();

        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('DELETE', "/api/tasks/$taskId", ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['id' => $task->getId()]
            )
        );
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['id' => $subtask->getId()]
            )
        );
    }

    public function testDeleteSubTaskKeepParentTask()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo]);
        $subtask = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo, 'parent' => $task]);

        $taskId = $subtask->getId();

        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('DELETE', "/api/tasks/$taskId", ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();

        $this->assertInstanceOf(
            Task::class,
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['id' => $task->getId()]
            )
        );
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['id' => $subtask->getId()]
            )
        );
    }

    public function testDeleteTaskWithCompletedSubTaskForbidden()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo, 'canDelete' => false]);
        $subtask = TaskFactory::createOne(['owner' => $user, 'status' => Status::Done, 'parent' => $task]);

        $taskId = $task->getId();

        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('DELETE', "/api/tasks/$taskId", ['auth_bearer' => $token]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertInstanceOf(
            Task::class,
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['id' => $task->getId()]
            )
        );
        $this->assertInstanceOf(
            Task::class,
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['id' => $subtask->getId()]
            )
        );
    }

    public function testCompleteTaskWithUncompletedSubTaskForbidden()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo, 'canComplete' => false]);
        $subtask = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo, 'parent' => $task]);

        $taskId = $task->getId();

        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('PATCH', "/api/tasks/$taskId", [
            'auth_bearer' => $token,
            'json' => [
                'status' => Status::Done
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(403);

        $actualTask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $taskId]
        );

        $this->assertEquals(Status::ToDo, $actualTask->getStatus());
    }

    public function testUpdateTask()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'description' => 'Test description']);
        $taskId = $task->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('PATCH', "/api/tasks/$taskId", [
            'auth_bearer' => $token,
            'json' => [
                'description' => 'Another description',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $actualTask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $taskId]
        );

        $this->assertEquals('Another description', $actualTask->getDescription());
    }

    public function testUpdateTaskOfAnotherOwnerForbidden()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'description' => 'Test description']);
        $user1 = $this->createUser('test1@example.com', 'password');

        $taskId = $task->getId();
        $token = $this->getToken('test1@example.com', 'password');

        static::createClient()->request('PATCH', "/api/tasks/$taskId", [
            'auth_bearer' => $token,
            'json' => [
                'description' => 'Another description',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(403);

        $actualTask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $taskId]
        );

        $this->assertEquals('Test description', $actualTask->getDescription());
    }

    public function testCompleteTask()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo]);
        $taskId = $task->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('PATCH', "/api/tasks/$taskId", [
            'auth_bearer' => $token,
            'json' => [
                'status' => Status::Done
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $actualTask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $taskId]
        );

        $this->assertEquals(Status::Done, $actualTask->getStatus());
    }

    public function testCompleteSubTaskShouldUpdateCanCompleteForParentTasks()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo, 'canComplete' => false]);
        $subtask = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::ToDo, 'parent' => $task, 'canComplete' => true]
        );

        $taskId = $subtask->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('PATCH', "/api/tasks/$taskId", [
            'auth_bearer' => $token,
            'json' => [
                'status' => Status::Done
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $actualSubtask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $subtask->getId()]
        );

        $this->assertEquals(Status::Done, $actualSubtask->getStatus());

        $actualTask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $task->getId()]
        );

        $this->assertTrue($actualTask->isCanComplete());
    }

    public function testUncompletedSubTaskShouldUpdateCanCompleteForParentTasks()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo, 'canComplete' => false]);
        $subtask1 = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::ToDo, 'parent' => $task, 'canComplete' => false]
        );
        $subtask2 = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::Done, 'parent' => $task, 'canComplete' => true]
        );
        $subsubtask1 = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::ToDo, 'parent' => $subtask1, 'canComplete' => true]
        );
        $subsubtask2 = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::Done, 'parent' => $subtask1, 'canComplete' => true]
        );


        $taskId = $subsubtask1->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('PATCH', "/api/tasks/$taskId", [
            'auth_bearer' => $token,
            'json' => [
                'status' => Status::Done
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $actualSubSubtask1 = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $subsubtask1->getId()]
        );

        $this->assertEquals(Status::Done, $actualSubSubtask1->getStatus());

        $actualSubtask1 = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $subtask1->getId()]
        );

        $this->assertEquals(Status::ToDo, $actualSubtask1->getStatus());
        $this->assertTrue($actualSubtask1->isCanComplete());

        $actualTask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $task->getId()]
        );

        $this->assertFalse($actualTask->isCanComplete());
    }

    public function testCompleteSubTaskShouldUpdateCanDeleteForParentTasks()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo, 'canDelete' => true]);
        $subtask = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::ToDo, 'parent' => $task, 'canComplete' => true]
        );

        $taskId = $subtask->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('PATCH', "/api/tasks/$taskId", [
            'auth_bearer' => $token,
            'json' => [
                'status' => Status::Done
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $actualSubtask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $subtask->getId()]
        );

        $this->assertEquals(Status::Done, $actualSubtask->getStatus());

        $actualTask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $task->getId()]
        );

        $this->assertFalse($actualTask->isCanDelete());
    }

    public function testCompletedSubTaskShouldUpdateCanDeleteForParentTasks()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo, 'canDelete' => true]);
        $subtask1 = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::ToDo, 'parent' => $task, 'canDelete' => true]
        );
        $subtask2 = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::ToDo, 'parent' => $task, 'canDelete' => true]
        );
        $subsubtask1 = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::ToDo, 'parent' => $subtask1, 'canDelete' => true]
        );
        $subsubtask2 = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::ToDo, 'parent' => $subtask1, 'canDelete' => true]
        );


        $taskId = $subsubtask1->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('PATCH', "/api/tasks/$taskId", [
            'auth_bearer' => $token,
            'json' => [
                'status' => Status::Done
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $actualSubSubtask1 = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $subsubtask1->getId()]
        );

        $this->assertEquals(Status::Done, $actualSubSubtask1->getStatus());
        $this->assertFalse($actualSubSubtask1->isCanDelete());

        $actualSubtask1 = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $subtask1->getId()]
        );

        $this->assertEquals(Status::ToDo, $actualSubtask1->getStatus());
        $this->assertFalse($actualSubtask1->isCanDelete());

        $actualTask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $task->getId()]
        );

        $this->assertFalse($actualTask->isCanDelete());
    }

    public function testCompletedSubTask()
    {
        $user = $this->createUser('test@example.com', 'password');
        $userId = $user->getId();
        $token = $this->getToken('test@example.com', 'password');
        static::createClient()->request('POST', '/api/tasks', [
            'auth_bearer' => $token,
            'json' => [
                "owner" => "/api/users/{$userId}",
                'status' => Status::ToDo,
                'priority' => 1,
                'title' => 'Test Task',
                'description' => 'test',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();

        $this->assertTrue(
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['title' => 'Test Task']
            )->isCanComplete()
        );
        $taskId = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['title' => 'Test Task']
        )->getId();
        static::createClient()->request('POST', '/api/tasks', [
            'auth_bearer' => $token,
            'json' => [
                "owner" => "/api/users/{$userId}",
                'parent' => "/api/tasks/{$taskId}",
                'status' => Status::ToDo,
                'priority' => 1,
                'title' => 'Child 1 Of Task',
                'description' => 'test',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertTrue(
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['title' => 'Child 1 Of Task']
            )->isCanComplete()
        );

        static::createClient()->request('POST', '/api/tasks', [
            'auth_bearer' => $token,
            'json' => [
                "owner" => "/api/users/{$userId}",
                'parent' => "/api/tasks/{$taskId}",
                'status' => Status::ToDo,
                'priority' => 1,
                'title' => 'Child 2 Of Task',
                'description' => 'test',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertTrue(
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['title' => 'Child 2 Of Task']
            )->isCanComplete()
        );

        $subtaskId = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['title' => 'Child 2 Of Task']
        )->getId();
        static::createClient()->request('PATCH', "/api/tasks/$subtaskId", [
            'auth_bearer' => $token,
            'json' => [
                'status' => Status::Done
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testUncompletedSubTaskShouldUpdateCanDeleteForParentTasks()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user, 'status' => Status::ToDo, 'canDelete' => false]);
        $subtask = TaskFactory::createOne(
            ['owner' => $user, 'status' => Status::Done, 'parent' => $task, 'canComplete' => true]
        );

        $taskId = $subtask->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('PATCH', "/api/tasks/$taskId", [
            'auth_bearer' => $token,
            'json' => [
                'status' => Status::ToDo
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $actualSubtask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $subtask->getId()]
        );

        $this->assertEquals(Status::ToDo, $actualSubtask->getStatus());

        $actualTask = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
            ['id' => $task->getId()]
        );

        $this->assertTrue($actualTask->isCanDelete());
    }

    /**
     * @dataProvider urlProvider
     */
    public function testUnauthorizedAccess($method, $url): void
    {
        static::createClient()->request($method, $url);

        $this->assertResponseStatusCodeSame(401);
    }

    public function urlProvider(): array
    {
        return [
            ['GET', '/api/tasks',],
            ['POST', '/api/tasks',],
            ['GET', "/api/tasks/1",],
            ['PUT', "/api/tasks/1",],
            ['PATCH', "/api/tasks/1",],
            ['DELETE', '/api/tasks/1',],
        ];
    }

    protected function createUser(string $email, string $password): User
    {
        $container = self::getContainer();

        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $container->get('security.user_password_hasher')->hashPassword($user, $password)
        );

        $manager = $container->get('doctrine')->getManager();
        $manager->persist($user);
        $manager->flush();

        return $user;
    }

    protected function getToken(string $email, string $password): string
    {
        $response = static::createClient()->request('POST', '/auth', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
        ]);

        return $response->toArray()['token'];
    }
}
