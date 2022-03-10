<?php

namespace Tests\API;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Tests\TestCase;

!defined('DOC_PATH') && define('DOC_PATH', 'docs/api-docs.yaml');

class WishTest extends TestCase
{
    use RefreshDatabase;

    const MESSAGE = 'I want a book';

    private User $user;
    private User $anotherUser;

    private array $header;
    private array $anotherUserHeader;

    protected function setUp(): void
    {
        parent::setUp();

        $userData = [
            'email' => 'no-reply@gmail.com',
            'password' => 'myPassword',
        ];
        $this->json('POST', '/api/v1/auth/signup', $userData);
        $loginResponse = $this->json('POST', '/api/v1/auth/login', $userData);
        $this->user = User::find(1);
        $this->header = ['Authorization' => 'Bearer ' . $loginResponse->json(['token'])];

        $anotherUserData = [
            'email' => 'another@gmail.com',
            'password' => 'anotherPassword',
        ];
        $this->json('POST', '/api/v1/auth/signup', $anotherUserData);
        $anotherUserLoginResponse = $this->json('POST', '/api/v1/auth/login', $anotherUserData);
        $this->anotherUser = User::find(2);
        $this->anotherUserHeader = ['Authorization' => 'Bearer ' . $anotherUserLoginResponse->json(['token'])];
    }

    public function testStoreWish()
    {
        $method = 'POST';
        $routeURI = '/api/v1/wishes';
        $data = ['message' => self::MESSAGE];
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, $data, $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);

        $this->assertArrayHasKey('message', $response->json());
        $this->assertSame(self::MESSAGE, $response->json()['message']);
        $this->assertDatabaseHas('wishes', ['message' => self::MESSAGE]);
    }

    public function testStoreWishShouldGetCode401WithoutToken()
    {
        $method = 'POST';
        $routeURI = '/api/v1/wishes';
        $data = ['message' => self::MESSAGE];
        $response = $this->json($method, $routeURI, $data);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(401);
        $this->assertDatabaseMissing('wishes', ['message' => self::MESSAGE]);
    }

    public function testStoreWishShouldGetCode422WithoutValidInput()
    {
        $method = 'POST';
        $routeURI = '/api/v1/wishes';
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, [], $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(422);
        $this->assertDatabaseMissing('wishes', ['message' => self::MESSAGE]);
    }

    public function testGetTheWishShouldGet404WithNonExistId()
    {
        $this->actingAs($this->user)
            ->json('POST', '/api/v1/wishes', ['message' => self::MESSAGE], $this->header);

        $method = 'GET';
        $routeURI = '/api/v1/wishes/10000';
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, [], $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(404);
    }

    public function testGetTheWishShouldGet404WithOthersToken()
    {
        $this->actingAs($this->user)
            ->json('POST', '/api/v1/wishes', ['message' => self::MESSAGE], $this->header);

        $method = 'GET';
        $routeURI = '/api/v1/wishes/1';

        $response = $this->actingAs($this->anotherUser)
            ->json($method, $routeURI, [], $this->anotherUserHeader);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(404);
        $this->assertDatabaseHas('wishes', ['message' => self::MESSAGE]);
    }

    public function testGetTheWish()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'aa'], $this->header);
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'bb'], $this->header);
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'cc'], $this->header);

        $method = 'GET';
        $routeURI = '/api/v1/wishes/2';
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, [], $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);

        $this->assertArrayHasKey('message', $response->json());
        $this->assertSame('bb', $response->json()['message']);
    }

    public function testGetWishList()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'aa'], $this->header);
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'bb'], $this->header);
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'cc'], $this->header);
        $this->actingAs($this->anotherUser)->json('POST', '/api/v1/wishes', ['message' => 'dd'], $this->anotherUserHeader);

        $method = 'GET';
        $routeURI = '/api/v1/wishes';
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, [], $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);

        $this->assertSame(3, $response->json()['total']);
        $wishes = Collection::make($response->json()['data']);
        $this->assertContains('bb', $wishes->pluck('message')->toArray());
    }

    public function testGetWishListWithDraftStatus()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'aa'], $this->header);
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'bb'], $this->header);
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'cc'], $this->header);

        $method = 'GET';
        $routeURI = '/api/v1/wishes';
        $query = ['status' => 'draft'];
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, $query, $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);
        $this->assertSame(3, $response->json()['total']);
    }

    public function testGetWishListShouldGetCode401WithoutToken()
    {
        $method = 'GET';
        $routeURI = '/api/v1/wishes';
        $response = $this->json($method, $routeURI, []);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(401);
    }

    public function testUpdateWish()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => self::MESSAGE], $this->header);

        $method = 'PUT';
        $routeURI = '/api/v1/wishes/1';
        $data = ['message' => 'updated'];
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, $data, $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);

        $this->assertArrayHasKey('message', $response->json());
        $this->assertSame('updated', $response->json()['message']);
        $this->assertDatabaseHas('wishes', ['message' => 'updated']);
        $this->assertDatabaseMissing('wishes', ['message' => 'aa']);
    }

    public function testUpdateWishShouldGetCode401WithoutToken()
    {
        $method = 'PUT';
        $routeURI = '/api/v1/wishes/1';
        $data = ['message' => self::MESSAGE];
        $response = $this->json($method, $routeURI, $data);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(401);

        $this->assertDatabaseMissing('wishes', ['message' => self::MESSAGE]);
    }

    public function testUpdateWishShouldGetCode404WithWrongToken()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'aa'], $this->header);

        $method = 'PUT';
        $routeURI = '/api/v1/wishes/1';
        $data = ['message' => 'updated'];
        $response = $this->actingAs($this->anotherUser)
            ->json($method, $routeURI, $data, $this->anotherUserHeader);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(404);

        $this->assertDatabaseMissing('wishes', ['message' => 'updated']);
    }

    public function testUpdateWishShouldGetCode422WithoutValidInput()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'aa'], $this->header);

        $method = 'PUT';
        $routeURI = '/api/v1/wishes/1';
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, [], $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(422);
    }

    public function testDeleteWish()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => self::MESSAGE], $this->header);

        $method = 'DELETE';
        $routeURI = '/api/v1/wishes/1';
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, [], $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);

        $this->assertDatabaseMissing('wishes', ['message' => self::MESSAGE]);
    }

    public function testDeleteWishShouldGetCode401WithoutToken()
    {
        $method = 'DELETE';
        $routeURI = '/api/v1/wishes/1';
        $response = $this->json($method, $routeURI);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(401);
    }

    public function testDeleteWishShouldGetCode404WithWrongToken()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => self::MESSAGE], $this->header);

        $method = 'DELETE';
        $routeURI = '/api/v1/wishes/1';
        $response = $this->actingAs($this->anotherUser)
            ->json($method, $routeURI, [], $this->anotherUserHeader);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(404);

        $this->assertDatabaseHas('wishes', ['message' => self::MESSAGE]);
    }

    public function testSubmitWish()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => self::MESSAGE], $this->header);

        $method = 'POST';
        $routeURI = '/api/v1/wishes/1/submit';
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, [], $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);
    }

    public function testGetSubmittedWishList()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => self::MESSAGE], $this->header);
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => 'aa'], $this->header);
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes/1/submit', [], $this->header);

        $method = 'GET';
        $routeURI = '/api/v1/wishes';
        $query = ['status' => 'submitted'];
        $response = $this->actingAs($this->user)
            ->json($method, $routeURI, $query, $this->header);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);

        $this->assertSame(1, $response->json()['total']);
        $wishes = Collection::make($response->json()['data']);
        $this->assertContains(self::MESSAGE, $wishes->pluck('message')->toArray());
    }

    public function testSubmitWishShouldGetCode401WithoutToken()
    {
        $method = 'POST';
        $routeURI = '/api/v1/wishes/1/submit';
        $response = $this->json($method, $routeURI);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(401);
    }

    public function testSubmitWishShouldGetCode404WithWrongToken()
    {
        $this->actingAs($this->user)->json('POST', '/api/v1/wishes', ['message' => self::MESSAGE], $this->header);

        $method = 'POST';
        $routeURI = '/api/v1/wishes/1/submit';
        $response = $this->actingAs($this->anotherUser)
            ->json($method, $routeURI, [], $this->anotherUserHeader);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(404);
    }
}
