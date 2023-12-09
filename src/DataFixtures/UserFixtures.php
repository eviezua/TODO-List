<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $userPasswordHasherInterface;

    public function __construct (UserPasswordHasherInterface $userPasswordHasherInterface)
    {
        $this->userPasswordHasherInterface = $userPasswordHasherInterface;
    }
    public function load(ObjectManager $manager): void
    {
        $user1 = new User();
        $user1->setEmail('user1@example.com');
        $user1->setPassword($this->userPasswordHasherInterface->hashPassword($user1, 'password'));
        $manager->persist($user1);
        $manager->flush();
    }
}
