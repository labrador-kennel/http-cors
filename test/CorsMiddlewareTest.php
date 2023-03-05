<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\PHPUnit\AsyncTestCase;
use League\Uri\Http;
use PHPUnit\Framework\MockObject\MockObject;

class CorsMiddlewareTest extends AsyncTestCase {

    private function createRequest(string $method, string $uri, array $headers = []) : Request {
        $mock = $this->createMock(Client::class);
        $headers = array_merge([], ['Origin' => 'https://labrador.example.com'], $headers);
        return new Request($mock, $method, Http::createFromString($uri), $headers);
    }

    private function configuration(array $overrides = []) : ConfigurationLoader {
        $configuration = new ArrayConfiguration(array_merge([], [
            'origins' => ['https://labrador.example.com'],
            'max_age' => 86400,
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'allowed_headers' => ['X-Custom-Req-Header', 'Content-Type'],
            'exposable_headers' => ['X-Custom-Res-Header'],
            'allow_credentials' => true
        ], $overrides));
        return new SimpleConfigurationLoader($configuration);
    }

    public function testNonOptionsRequestWithNoOriginDoesNotLoadConfiguration() {
        $request = new Request(
            $this->createMock(Client::class),
            'GET',
            Http::createFromString('https://example.com')
        );

        /** @var MockObject|ConfigurationLoader $loader */
        $loader = $this->getMockBuilder(ConfigurationLoader::class)->getMock();
        $loader->expects($this->never())->method('loadConfiguration');

        /** @var MockObject|RequestHandler $requestHandler */
        $requestHandler = $this->getMockBuilder(RequestHandler::class)->getMock();
        $requestHandler->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn(new Response());

        $subject = new CorsMiddleware($loader);

        /** @var Response $response */
        $response = $subject->handleRequest($request, $requestHandler);

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testNonOptionsRequestForwardedToRequestHandler() {
        $request = $this->createRequest('GET', '/some/path');
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response();
        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($response);

        $middleware = new CorsMiddleware($this->configuration());
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame($response, $actualResponse);
    }

    public function testNonOptionsRequestHasOriginHeaderIfMatchesConfiguration() {
        $request = $this->createRequest('GET', '/some/path');
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response();
        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($response);

        $middleware = new CorsMiddleware($this->configuration());
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame(
            'https://labrador.example.com',
            $actualResponse->getHeader('Access-Control-Allow-Origin')
        );
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
            ->willReturn($response);

        $middleware = new CorsMiddleware($this->configuration([
            'allow_credentials' => false
        ]));
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Credentials'));
    }

    public function testNonOptionsRequestRespectsExistingVaryHeader() {
        $request = $this->createRequest('GET', '/some/path');
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response(HttpStatus::OK, ['Vary' => 'Content-Type']);
        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($response);

        $middleware = new CorsMiddleware($this->configuration([
            'allow_credentials' => false
        ]));
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame('Content-Type, Origin', $actualResponse->getHeader('Vary'));
    }

    public function testNonOptionRequestWithoutCorrectOriginDoesNotHaveHeader() {
        $request = $this->createRequest('GET', '/some/path', ['Origin' => 'http://not-ours.example.com']);
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response();

        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($response);

        $middleware = new CorsMiddleware($this->configuration());
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertNull($actualResponse->getHeader('Access-Control-Allow-Origin'));
    }

    public function testNonOptionRequestRespectsWildcardOrigin() {
        $request = $this->createRequest('POST', '/some/path', [
            'Origin' => 'https://' . md5(random_bytes(8)) . '.example.com',
        ]);
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response();

        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($response);

        $config = $this->configuration([
            'origins' => ['*']
        ]);
        $middleware = new CorsMiddleware($config);
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame('*', $actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
    }

    public function testNonOptionRequestOriginCheckIsCaseInsensitive() {
        $request = $this->createRequest('POST', '/some/path', [
            'Origin' => 'https://LABRADOR.example.com',
        ]);
        $mock = $this->createMock(RequestHandler::class);
        $response = new Response();

        $mock->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn($response);

        $config = $this->configuration();
        $middleware = new CorsMiddleware($config);
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame(
            'https://labrador.example.com',
            $actualResponse->getHeader('Access-Control-Allow-Origin')
        );
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
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
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame(
            'https://labrador.example.com',
            $actualResponse->getHeader('Access-Control-Allow-Origin')
        );
        $this->assertSame(
            'GET, POST, PUT, DELETE',
            $actualResponse->getHeader('Access-Control-Allow-Methods')
        );
        $this->assertSame(
            'X-Custom-Req-Header, Content-Type',
            $actualResponse->getHeader('Access-Control-Allow-Headers')
        );
        $this->assertSame('X-Custom-Res-Header', $actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
        $this->assertSame(86400, (int) $actualResponse->getHeader('Access-Control-Max-Age'));
    }

    public function testOptionRequestOriginHeaderCaseInsensitive() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'https://LABRADOR.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Custom-Req-Header'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration());
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame(
            'https://labrador.example.com',
            $actualResponse->getHeader('Access-Control-Allow-Origin')
        );
        $this->assertSame(
            'GET, POST, PUT, DELETE',
            $actualResponse->getHeader('Access-Control-Allow-Methods')
        );
        $this->assertSame(
            'X-Custom-Req-Header, Content-Type',
            $actualResponse->getHeader('Access-Control-Allow-Headers')
        );
        $this->assertSame('X-Custom-Res-Header', $actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
        $this->assertSame(86400, (int) $actualResponse->getHeader('Access-Control-Max-Age'));
    }

    public function testOptionRequestRespectsWildcardOrigin() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'https://' . md5(random_bytes(8)) . '.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Custom-Req-Header'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $config = $this->configuration([
            'origins' => ['*']
        ]);
        $middleware = new CorsMiddleware($config);
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame('*', $actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame(
            'GET, POST, PUT, DELETE',
            $actualResponse->getHeader('Access-Control-Allow-Methods')
        );
        $this->assertSame(
            'X-Custom-Req-Header, Content-Type',
            $actualResponse->getHeader('Access-Control-Allow-Headers')
        );
        $this->assertSame('X-Custom-Res-Header', $actualResponse->getHeader('Access-Control-Expose-Headers'));
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
        $this->assertSame(86400, (int) $actualResponse->getHeader('Access-Control-Max-Age'));
    }


    public function testOptionRequestAllowedHeadersCaseInsensitive() {
        $request = $this->createRequest('OPTIONS', '/some/path', [
            'Origin' => 'https://' . md5(random_bytes(8)) . '.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-CUSTOM-REQ-HEADER'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $config = $this->configuration([
            'origins' => ['*']
        ]);
        $middleware = new CorsMiddleware($config);
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame('*', $actualResponse->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame(
            'GET, POST, PUT, DELETE',
            $actualResponse->getHeader('Access-Control-Allow-Methods')
        );
        $this->assertSame(
            'X-Custom-Req-Header, Content-Type',
            $actualResponse->getHeader('Access-Control-Allow-Headers')
        );
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
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame(
            'https://labrador.example.com',
            $actualResponse->getHeader('Access-Control-Allow-Origin')
        );
        $this->assertSame('GET, POST, PUT, DELETE', $actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertSame(
            'X-Custom-Req-Header, Content-Type',
            $actualResponse->getHeader('Access-Control-Allow-Headers')
        );
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
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame(
            'https://labrador.example.com',
            $actualResponse->getHeader('Access-Control-Allow-Origin')
        );
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
            'Access-Control-Request-Headers' => 'X-Custom-Req-Header, Content-Type'
        ]);
        $mock = $this->createMock(RequestHandler::class);

        $mock->expects($this->never())
            ->method('handleRequest');

        $middleware = new CorsMiddleware($this->configuration([
            'exposable_headers' => []
        ]));
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame(
            'https://labrador.example.com',
            $actualResponse->getHeader('Access-Control-Allow-Origin')
        );
        $this->assertSame('GET, POST, PUT, DELETE', $actualResponse->getHeader('Access-Control-Allow-Methods'));
        $this->assertSame(
            'X-Custom-Req-Header, Content-Type',
            $actualResponse->getHeader('Access-Control-Allow-Headers')
        );
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
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::FORBIDDEN, $actualResponse->getStatus());
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
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::METHOD_NOT_ALLOWED, $actualResponse->getStatus());
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

        $middleware = new CorsMiddleware($this->configuration([
            'allowed_headers' => ['X-Not-Set-In-Options', 'Content-Type']
        ]));
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::FORBIDDEN, $actualResponse->getStatus());
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
        $actualResponse = $middleware->handleRequest($request, $mock);

        $this->assertSame(HttpStatus::OK, $actualResponse->getStatus());
        $this->assertSame(
            'https://labrador.example.com',
            $actualResponse->getHeader('Access-Control-Allow-Origin')
        );
        $this->assertSame(
            'GET, POST, PUT, DELETE',
            $actualResponse->getHeader('Access-Control-Allow-Methods')
        );
        $this->assertSame(
            'X-Custom-Req-Header, Content-Type',
            $actualResponse->getHeader('Access-Control-Allow-Headers')
        );
        $this->assertSame(
            'X-Custom-Res-Header',
            $actualResponse->getHeader('Access-Control-Expose-Headers')
        );
        $this->assertSame('true', $actualResponse->getHeader('Access-Control-Allow-Credentials'));
        $this->assertFalse($actualResponse->hasHeader('Access-Control-Max-Age'));
    }
}
