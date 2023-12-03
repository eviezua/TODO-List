<?php

namespace App\DataFixtures;

use App\Entity\Status;
use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TaskFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $task = new Task();
        $task->setStatus(Status::ToDo);
        $task->setPriority(6);
        $task->setTitle('My First Task');
        $task->setCreatedAt(new \DateTimeImmutable());
        $task->setCompletedAt(new \DateTimeImmutable());

        $manager->persist($task);
        $manager->flush();

        $task1 = new Task();
        $task1->setStatus(Status::ToDo);
        $task1->setPriority(1);
        $task1->setTitle('My Second Task');
        $task1->setCreatedAt(new \DateTimeImmutable());
        $task1->setCompletedAt(new \DateTimeImmutable());

        $manager->persist($task1);
        $manager->flush();
    }
}
