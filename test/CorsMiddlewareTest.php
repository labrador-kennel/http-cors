<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors\Test;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use Cspray\Labrador\Http\Cors\ArrayConfiguration;
use Cspray\Labrador\Http\Cors\Configuration;
use Cspray\Labrador\Http\Cors\CorsMiddleware;
use League\Uri\Http;

class CorsMiddlewareTest extends AsyncTestCase {

    private function createRequest(string $method, string $uri, array $headers = []) : Request {
        $mock = $this->createMock(Client::class);
        $headers = array_merge([], ['Origin' => 'https://labrador.example.com'], $headers);
        return new Request($mock, $method, Http::createFromString($uri), $headers);
    }

    private function configuration(array $overrides = []) : Configuration {
        return new ArrayConfiguration(array_merge([], [
            'origin' => 'https://labrador.example.com',
            'max_age' => 86400,
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'allowed_headers' => ['X-Custom-Req-Header', 'Content-Type'],
            'exposable_headers' => ['X-Custom-Res-Header'],
            'allow_credentials' => true
        ], $overrides));
    }

    public function testNonOptionsRequestForwardedToRequestHandler() {
        $request = $this->createRequest('GET', '/some/path');
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response();
        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn(new Success($response));

        $middleware = new CorsMiddleware($this->configuration());
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame($response, $actualResponse);
    }

    public function testNonOptionsRequestHasOriginHeaderIfMatchesConfiguration() {
        $request = $this->createRequest('GET', '/some/path');
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response();
        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn(new Success($response));

        $middleware = new CorsMiddleware($this->configuration());
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::OK, $actualResponse->getStatus());
        $this->assertSame('https://labrador.example.com', $actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('Origin', $actualResponse->getHeader('Vary'));
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
    }

    public function testNonOptionsRequestRespectsAllowCredentialsBoolean() {
        $request = $this->createRequest('GET', '/some/path');
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response();
        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn(new Success($response));

        $middleware = new CorsMiddleware($this->configuration([
            'allow_credentials' => false
        ]));
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Credentials'));
    }

    public function testNonOptionsRequestRespectsExistingVaryHeader() {
        $request = $this->createRequest('GET', '/some/path');
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response(Status::OK, ['Vary' => 'Content-Type']);
        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn(new Success($response));

        $middleware = new CorsMiddleware($this->configuration([
            'allow_credentials' => false
        ]));
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame('Content-Type, Origin', $actualResponse->getHeader('Vary'));
    }

    public function testNonOptionRequestWithoutCorrectOriginDoesNotHaveHeader() {
        $request = $this->createRequest('GET', '/some/path', ['Origin' => 'http://not-ours.example.com']);
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response();

        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn(new Success($response));

        $middleware = new CorsMiddleware($this->configuration());
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::OK, $actualResponse->getStatus());
        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Origin'));
    }

    public function testOptionRequestWithCorrectOriginHasAllHeaders() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'https://labrador.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Custom-Req-Header'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration());
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::OK, $actualResponse->getStatus());
        $this->assertSame('https://labrador.example.com', $actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST, PUT, DELETE', $actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertSame('X-Custom-Req-Header, Content-Type', $actualResponse->getHeader('Access-Control-Allow-Headers'));
        $this->assertSame('X-Custom-Res-Header', $actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
        $this->assertSame(86400, (int) $actualResponse->getHeader('Access-Control-Max-Age'));
    }

    public function testOptionRequestRespectsWildcardOrigin() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'https://labrador.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Custom-Req-Header'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration([
            'origin' => '*'
        ]));
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::OK, $actualResponse->getStatus());
        $this->assertSame('*', $actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST, PUT, DELETE', $actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertSame('X-Custom-Req-Header, Content-Type', $actualResponse->getHeader('Access-Control-Allow-Headers'));
        $this->assertSame('X-Custom-Res-Header', $actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
        $this->assertSame(86400, (int) $actualResponse->getHeader('Access-Control-Max-Age'));
    }

    public function testOptionRequestRespectsAllowCredentialsFlag() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'https://labrador.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Custom-Req-Header'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration([
            'allow_credentials' => false
        ]));
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::OK, $actualResponse->getStatus());
        $this->assertSame('https://labrador.example.com', $actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST, PUT, DELETE', $actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertSame('X-Custom-Req-Header, Content-Type', $actualResponse->getHeader('Access-Control-Allow-Headers'));
        $this->assertSame('X-Custom-Res-Header', $actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Credentials'));
        $this->assertSame(86400, (int) $actualResponse->getHeader('Access-Control-Max-Age'));
    }

    public function testOptionRequestRespectsNoAllowedHeaders() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'https://labrador.example.com',
            'Access-Control-Request-Method' => 'POST'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration([
            'allowed_headers' => []
        ]));
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::OK, $actualResponse->getStatus());
        $this->assertSame('https://labrador.example.com', $actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST, PUT, DELETE', $actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Headers'));
        $this->assertSame('X-Custom-Res-Header', $actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
        $this->assertSame(86400, (int) $actualResponse->getHeader('Access-Control-Max-Age'));
    }

    public function testOptionRequestRespectsNoExposableHeaders() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'https://labrador.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Custom-Req-Header'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration([
            'exposable_headers' => []
        ]));
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::OK, $actualResponse->getStatus());
        $this->assertSame('https://labrador.example.com', $actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST, PUT, DELETE', $actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertSame('X-Custom-Req-Header, Content-Type', $actualResponse->getHeader('Access-Control-Allow-Headers'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
        $this->assertSame(86400, (int) $actualResponse->getHeader('Access-Control-Max-Age'));
    }

    public function testOptionRequestWithIncorrectOriginHasNoHeaders() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'http://some-invalid-origin.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Custom-Req-Header'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration());
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::FORBIDDEN, $actualResponse->getStatus());
        $this->assertNulL($actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Headers'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Max-Age'));
    }

    public function testOptionRequestWithIncorrectMethodHasNoHeaders() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'https://labrador.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Custom-Req-Header'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration(['allowed_methods' => ['GET']]));
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::METHOD_NOT_ALLOWED, $actualResponse->getStatus());
        $this->assertNulL($actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Headers'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Max-Age'));
    }

    public function testOptionRequestWithInvalidRequestHeadersHasNoResponseHeaders() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'https://labrador.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Custom-Req-Header, Content-Type'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration(['allowed_headers' => ['X-Not-Set-In-Options', 'Content-Type']]));
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::FORBIDDEN, $actualResponse->getStatus());
        $this->assertNulL($actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Headers'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertNull($actualResponse->getHeader('Access-Control-Max-Age'));
    }

    public function testNoMaxAgeSetDoesNotSetHeader() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Access-Control-Request-Method' => 'GET'
        ]);
        $mock = $this->createMock(RequestHandler::class);
        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration(['max_age' => null]));
        /** @var Response $actualResponse */
        $actualResponse = yield $middleware->handleRequest($request, $mock);

        $this->assertSame(Status::OK, $actualResponse->getStatus());
        $this->assertSame('https://labrador.example.com', $actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST, PUT, DELETE', $actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertSame('X-Custom-Req-Header, Content-Type', $actualResponse->getHeader('Access-Control-Allow-Headers'));
        $this->assertSame('X-Custom-Res-Header', $actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
        $this->assertFalse($actualResponse->hasHeader('Access-Control-Max-Age'));
    }

}