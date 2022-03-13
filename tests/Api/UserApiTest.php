<?php

namespace App\Tests\Api;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;

class UserApiTest extends ApiTestCase
{
    private $client;
    private $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->token = $this->getToken();
    }


    private function getToken($body = [])
    {
        if ($this->token) {
            return $this->token;
        }

        $response = $this->client->request(
            'POST',
            '/api/login',
            [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => ($body ?: [
                    'username' => 'admin@admin.fr',
                    'password' => '123456',
                ])
            ]
        );

        // $data = $response->toArray();
        // $this->token = $data['token'];
        // // $data = json_decode($response->getContent());
        // // $this->token = $data->token;

        // return $data['token'];
    }

    public function testUserApiGetCollection(): void
    {
        $response = $this->sendRequest('GET');

        $this->assertResponseIsSuccessful();

        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertCount(7, $response->toArray()['hydra:member']);
        //$this->assertMatchesResourceCollectionJsonSchema(User::class);
    }

    public function testUserApiPostItem(): void
    {
        //définition de l'utilisateur
        $newUser = [
            'email' => 'user@test.fr',
            'password' => '12356',
            'isActivated' => true,
            'roles' => ["ROLE_USER"]
        ];

        //test ajouter un utilisateur
        $response = $this->sendRequest('POST', null, $newUser);
        $data  = $response->toArray();

        //on vérifie que l'utilisateur a bien été créé
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertArrayHasKey('id', $data);
        // $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testUserApiGetItem(): void
    {
        $uri = $this->findIriBy(User::class, ['email' => 'admin@admin.fr']);

        $response = $this->sendRequest('GET', $uri);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'email' => 'admin@admin.fr',
        ]);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesRegularExpression('~^/api/users/\d+$~', $response->toArray()['@id']);
        // $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testUserApiUpdateItem(): void
    {
        $uri = $this->findIriBy(User::class, ['email' => 'testman@test.fr']);
        // $response = $this->sendRequest('PUT', $uri, ['isActivated' => false]);
        $response = $this->client->request('PUT', $uri, [
            'headers' => [
                'Content-Type' => 'application/ld+json'
            ],
            'auth_bearer' => $this->token,
            'json' => [
                'isActivated' => false
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertArrayHasKey('email', $response->toArray());
    }

    public function testUserApiDeleteItem(): void
    {
        $uri = $this->findIriBy(User::class, ['email' => 'testman@test.fr']);
        $response = $this->sendRequest("DELETE", $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);


        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'testman@test.fr'])
        );
    }

    public function testSimpleUserDeleteItem(): void
    {

        $response = $this->client->request(
            'POST',
            '/api/login',
            [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'username' => 'testman@test.fr',
                    'password' => '123456',
                ]
            ]
        );

        // $token = $response->toArray()['token'];

        $uri = $this->findIriBy(User::class, ['email' => 'admin@admin.fr']);
        //$response = static::createClient()->request('DELETE', $uri);
        $this->sendRequest('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testSimpleUserUpdateItem(): void
    {
        $response = $this->client->request(
            'POST',
            '/api/login',
            [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'username' => 'testman@test.fr',
                    'password' => '123456',
                ]
            ]
        );

        // $token = $response->toArray()['token'];

        $uri = $this->findIriBy(User::class, ['email' => 'admin@admin.fr']);
        // $response = static::createClient()->request('PUT', $uri,  [
        //     'headers' => [
        //         'Content-Type' => 'application/ld+json'
        //     ],
        //     'auth_bearer' => $token,
        //     'json' => [
        //         'isActivated' => false
        //     ]
        // ]);
        $this->sendRequest('DELETE', $uri, ['headers' => [
            'Content-Type' => 'application/ld+json'
        ], 'json' => [
            'isActivated' => false
        ]]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function sendRequest(string $method, string $uri = null, $data = [])
    {
        $uri ? $uri : ($uri = '/api/users');

        $response = $this->client->request($method, $uri, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            // 'auth_bearer' => $this->token,
            'json' => ($data ? $data : [])
        ]);

        return $response;
    }
}
