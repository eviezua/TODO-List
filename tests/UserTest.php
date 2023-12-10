<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Factory\UserFactory;
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

    public function testGetCollection()
    {
        $client = self::createClient();

        $user = $this->createUser('test@example.com', 'password');

        UserFactory::createMany(100);

        $client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(401);

        $token = $this->getToken('test@example.com', 'password');

        static::createClient()->request('GET', '/api/users', ['auth_bearer' => $token]);
        $this->assertResponseIsSuccessful();

        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            '@id' => '/api/users',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 101,
            'hydra:view' => [
                '@id' => '/api/users?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/api/users?page=1',
                'hydra:last' => '/api/users?page=4',
                'hydra:next' => '/api/users?page=2',
            ],
        ]);
    }

    public function testGetUser()
    {
        $client = static::createClient();

        $user = $this->createUser('test@example.com', 'password');

        $userId = $user->getId();

        $client->request('GET', "/api/users/{$userId}");
        $this->assertResponseStatusCodeSame(401);

        $token = $this->getToken('test@example.com', 'password');

        $response = static::createClient()->request('GET', "/api/users/{$userId}", ['auth_bearer' => $token]);
        $this->assertResponseIsSuccessful();

        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            '@type' => 'User',
            'userIdentifier' => 'test@example.com',
        ]);
    }

    public function testUpdateUser()
    {
        $client = static::createClient();
        $user1 = $this->createUser('test@example.com', 'password');

        $user = UserFactory::createOne(['email' => 'test1@example.com']);

        $userId = $user->getId();

        $client->request('PATCH', "/api/users/{$userId}");
        $this->assertResponseStatusCodeSame(401);

        $token = $this->getToken('test@example.com', 'password');

        $response = static::createClient()->request('PATCH', "/api/users/{$userId}", [
                'auth_bearer' => $token,
                'json' => [
                    'email' => 'test2@example.com',
                ],
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ]
            ]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            '@type' => 'User',
            'userIdentifier' => 'test2@example.com',
        ]);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(
                ['email' => 'test1@example.com']
            )
        );
    }

    public function testDeleteUser()
    {
        $client = static::createClient();
        $user1 = $this->createUser('test@example.com', 'password');

        $user = UserFactory::createOne(['email' => 'test1@example.com']);
        $userId = $user->getId();

        $client->request('DELETE', "/api/users/{$userId}");

        $this->assertResponseStatusCodeSame(401);

        $token = $this->getToken('test@example.com', 'password');
        $client->request('DELETE', "/api/users/{$userId}", ['auth_bearer' => $token]);

        $this->assertResponseIsSuccessful();
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
