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
                'createdAt' => "2023-12-10"
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/Task',
                '@type' => 'Task',
                "owner" => "/api/users/{$userId}",
                'status' => 'ToDo',
                'priority' => 1,
                'title' => 'Test Task',
                'description' => 'test',
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
        $task = TaskFactory::createOne(['owner' => $user]);
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

    public function testUpdateTask()
    {
        $user = $this->createUser('test@example.com', 'password');
        $task = TaskFactory::createOne(['owner' => $user]);
        $taskId = $task->getId();
        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('PATCH', "/api/tasks/$taskId", [
            'auth_bearer' => $token,
            'json' => [
                'status' => Status::Done,
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Task',
            '@id' => "/api/tasks/$taskId",
            '@type' => 'Task',
            'status' => 'Done'
        ]);
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
