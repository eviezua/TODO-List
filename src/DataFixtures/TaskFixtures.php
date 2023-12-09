<?php

namespace App\DataFixtures;

use App\Entity\Status;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
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
        $task = new Task();
        $task->setOwner($user1);
        $task->setStatus(Status::ToDo);
        $task->setPriority(2);
        $task->setTitle('My First Task');
        $task->setCreatedAt(new \DateTimeImmutable());
        $task->setCompletedAt(new \DateTimeImmutable());
        $task->setCompletedAt(new \DateTimeImmutable());

        $manager->persist($task);
        $manager->flush();

        $task1 = new Task();
        $task1->setOwner($user1);
        $task1->setStatus(Status::ToDo);
        $task1->setPriority(1);
        $task1->setTitle('My Second Task');
        $task1->setCreatedAt(new \DateTimeImmutable());
        $task1->setCompletedAt(new \DateTimeImmutable());

        $manager->persist($task1);
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
