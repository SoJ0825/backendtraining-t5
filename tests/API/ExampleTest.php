<?php

namespace Tests\API;

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Tests\TestCase;

!defined('DOC_PATH') && define('DOC_PATH', 'docs/api-docs.yaml');

class ExampleTest extends TestCase
{
    public function testHelloWorld()
    {
        $method = 'GET';
        $routeURI = '/api/hello';
        $response = $this->json($method, $routeURI);

        $validator = ValidatorBuilder::fromYaml(public_path(DOC_PATH))->getValidator();
        $conformed = $validator->validate($response->baseResponse, $routeURI, $method);

        $this->assertTrue($conformed);
        $response->assertStatus(200);
        $this->assertArrayHasKey('hello_world', $response->json());
    }
}
