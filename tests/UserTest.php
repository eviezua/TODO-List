<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserTest extends ApiTestCase
{
    use ResetDatabase;

    public function testLogin(): void
    {
        $client = self::createClient();
        $container = self::getContainer();

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword(
            $container->get('security.user_password_hasher')->hashPassword($user, '$3CR3T')
        );

        $manager = $container->get('doctrine')->getManager();
        $manager->persist($user);
        $manager->flush();

        // retrieve a token
        $response = $client->request('POST', '/auth', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => 'test@example.com',
                'password' => '$3CR3T',
            ],
        ]);

        $json = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $json);

        // test not authorized
        $client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(401);

        // test authorized
        $client->request('GET', '/api/users', ['auth_bearer' => $json['token']]);
        $this->assertResponseIsSuccessful();
    }

    public function testRegistration(): void
    {
        $client = self::createClient();

        $response = $client->request('POST', '/api/users', [
            'json' => [
                'email' => 'test@example.com',
                'password' => 'password',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();

        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/User',
                '@type' => 'User',
                'email' => 'test@example.com',
            ]
        );

        $userData = $response->toArray();

        $this->assertMatchesRegularExpression('~^/api/users/\d+$~', $userData['@id']);
        $this->assertNotEmpty($userData['password']);
    }
}
