<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Task;
use App\Factory\TaskFactory;
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
}