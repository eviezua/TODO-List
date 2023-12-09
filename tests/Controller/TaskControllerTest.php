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
        TaskFactory::createMany(2);

        $task = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy([]);

        $userId = $task->getOwner()->getId();

        $response = static::createClient()->request('GET', "/user/{$userId}/tasks");

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('tasks', $responseData);
    }

    /**
     * @dataProvider invalidTaskProvider
     */
    public function testCreateInvalidTask($params)
    {
        UserFactory::createOne();

        $user = static::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([]);

        $userId = $user->getId();

        $response = static::createClient()->request('GET', "/user/{$userId}/tasks/create?" . $params);

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
        UserFactory::createOne();

        $user = static::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([]);

        $userId = $user->getId();

        $response = static::createClient()->request('GET', "/user/{$userId}/tasks/create?title=TestCreateTask&description=test&priority=1");

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(['message' => 'Task created successfully'], $responseData);

        $task = static::getContainer()->get('doctrine')->getRepository(Task::class)->findOneBy([]);
        $this->assertEquals('TestCreateTask', $task->getTitle());
        $this->assertEquals('test', $task->getDescription());
        $this->assertEquals(1, $task->getPriority());
    }
}