<?php

namespace App\DataFixtures;

use App\Entity\Status;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function load(ObjectManager $manager): void
    {
        $user1 = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'user1@example.com']);;

        $task = new Task(new \DateTimeImmutable());
        $task->setOwner($user1);
        $task->setStatus(Status::Done);
        $task->setPriority(2);
        $task->setTitle('My First Task');
        $task->setCompletedAt(new \DateTimeImmutable());
        $task->setCompletedAt(new \DateTimeImmutable());

        $manager->persist($task);

        $task1 = new Task(new \DateTimeImmutable());
        $task1->setOwner($user1);
        $task1->setStatus(Status::ToDo);
        $task1->setPriority(1);
        $task1->setTitle('My Second Task');
        $task1->setCompletedAt(new \DateTimeImmutable());
        $task1->setCanComplete(false);

        $manager->persist($task1);

        $subtask1 = new Task(new \DateTimeImmutable());
        $subtask1->setOwner($user1);
        $subtask1->setParent($task1);
        $subtask1->setStatus(Status::ToDo);
        $subtask1->setPriority(1);
        $subtask1->setTitle('My ToDo Sub Task');
        $subtask1->setCompletedAt(new \DateTimeImmutable());

        $manager->persist($subtask1);

        $subtask2 = new Task(new \DateTimeImmutable());
        $subtask2->setOwner($user1);
        $subtask2->setParent($task1);
        $subtask2->setStatus(Status::Done);
        $subtask2->setPriority(1);
        $subtask2->setTitle('My Done Sub Task');
        $subtask2->setCompletedAt(new \DateTimeImmutable());

        $manager->persist($subtask2);


        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            // Add other fixtures as dependencies
            UserFixtures::class,
        ];
    }
}
