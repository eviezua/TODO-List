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

        static::createClient()->request('DELETE', "/api/tasks/$taskId", ['auth_bearer' => $token]);

        $this->assertResponseStatusCodeSame(403);

        $this->assertInstanceOf(
            Task::class,
            static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy(
                ['id' => $taskId]
            )
        );
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
