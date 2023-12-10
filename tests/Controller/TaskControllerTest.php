<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Status;
use App\Entity\Task;
use App\Entity\User;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TaskControllerTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    public function testGetTaskList(): void
    {
        $user = $this->createUser('test@example.com', 'password');

        TaskFactory::createMany(
            2,
            ['owner' => $user]
        );

        $task = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy([]);

        $userId = $task->getOwner()->getId();

        $token = $this->getToken('test@example.com', 'password');

        $response = static::createClient()->request('GET', "/user/{$userId}/tasks", ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('tasks', $responseData);
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

    /**
     * @dataProvider invalidTaskProvider
     */
    public function testCreateInvalidTask($params)
    {
        $user = $this->createUser('test@example.com', 'password');

        $userId = $user->getId();
        $token = $this->getToken('test@example.com', 'password');

        $response = static::createClient()->request(
            'GET',
            "/user/{$userId}/tasks/create?" . $params,
            ['auth_bearer' => $token]
        );

        $this->assertEquals(400, $response->getStatusCode());

        $task = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy([]);
        $this->assertNull($task);

        $this->expectException(\Symfony\Component\HttpClient\Exception\ClientException::class);

        $content = $response->getContent();
    }

    public function invalidTaskProvider()
    {
        return [
            [
                'params' => 'description=test',
            ],
            [
                'params' => 'title=TestCreateTask&description=test',
            ],
            [
                'params' => 'description=test&priority=1',
            ],
        ];
    }

    public function testCreateTask()
    {
        $user = $this->createUser('test@example.com', 'password');

        $userId = $user->getId();

        $token = $this->getToken('test@example.com', 'password');

        $response = static::createClient()->request(
            'GET',
            "/user/{$userId}/tasks/create?title=TestCreateTask&description=test&priority=1",
            [
                'auth_bearer' => $token
            ]
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(['message' => 'Task created successfully'], $responseData);

        $task = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy([]);
        $this->assertEquals('TestCreateTask', $task->getTitle());
        $this->assertEquals('test', $task->getDescription());
        $this->assertEquals(1, $task->getPriority());
    }
}