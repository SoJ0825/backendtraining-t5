<?php

namespace Tests\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Tests\TestCase;

!defined('DOC_PATH') && define('DOC_PATH', 'docs/api-docs.yaml');

class AuthTest extends TestCase
{
    use RefreshDatabase;

    const EMAIL = 'no-reply@gmail.com';
    const PASSWORD = 'myPassword';

    public function testSignup()
    {
        $method = 'POST';
        $routeURI = '/api/v1/auth/signup';
        $data = [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ];
        $response = $this->json($method, $routeURI, $data);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', ['email' => self::EMAIL]);
        $this->assertDatabaseMissing('users', ['password' => self::PASSWORD]);
    }

    public function invalidAuthInputDataProvider(): array
    {
        return [
            'empty request body' => [[]],
            'missing password' => [['email' => self::EMAIL]],
            'missing email' => [['password' => self::PASSWORD]],
            'invalid email format' => [[
                'email' => 'not-email-format',
                'password' => self::PASSWORD,
            ]],
        ];
    }

    /** @dataProvider invalidAuthInputDataProvider */
    public function testSignupShouldGetCode422WithoutValidInput(array $requestBody)
    {
        $method = 'POST';
        $routeURI = '/api/v1/auth/signup';
        $data = $requestBody;
        $response = $this->json($method, $routeURI, $data);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(422);
    }

    public function testLogin()
    {
        // Signup
        $data = [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ];
        $this->json('POST', '/api/v1/auth/signup', $data);

        // Login
        $method = 'POST';
        $routeURI = '/api/v1/auth/login';
        $response = $this->json($method, $routeURI, $data);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);
        $this->assertArrayHasKey('token', $response->json());
    }

    /** @dataProvider invalidAuthInputDataProvider */
    public function testLoginShouldGetCode422WithoutValidInput(array $requestBody)
    {
        $method = 'POST';
        $routeURI = '/api/v1/auth/login';
        $data = $requestBody;
        $response = $this->json($method, $routeURI, $data);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(422);
    }

    public function testLoginShouldGetCode404WithNonExistUser()
    {
        $method = 'POST';
        $routeURI = '/api/v1/auth/login';
        $data = [
            'email' => 'non-exist-user@gmail.com',
            'password' => self::PASSWORD,
        ];
        $response = $this->json($method, $routeURI, $data);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(404);
    }

    public function testLoginShouldGetCode401WithWrongPassword()
    {
        // Signup
        $data = [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ];
        $this->json('POST', '/api/v1/auth/signup', $data);

        // Login
        $method = 'POST';
        $routeURI = '/api/v1/auth/login';
        $data = [
            'email' => self::EMAIL,
            'password' => 'WrongPassword',
        ];
        $response = $this->json($method, $routeURI, $data);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(401);
    }
}
