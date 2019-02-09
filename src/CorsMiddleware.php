<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;

use function Amp\call;

final class CorsMiddleware implements Middleware {

    private $configuration;

    public function __construct(Configuration $configuration) {
        $this->configuration = $configuration;
    }

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Promise {
        return call(function() use($request, $requestHandler) {
            if ($request->getMethod() === 'OPTIONS') {
                return $this->handleOptionRequest($request);
            }

            /** @var Response $response */
            $response = yield $requestHandler->handleRequest($request);

            $origin = $this->configuration->getOrigin();
            if ($request->getHeader('Origin') === $origin) {
                $response->setHeader('Access-Control-Allow-Origin', $origin);
                $varyHeader = $response->getHeader('Vary');
                $varyHeader = isset($varyHeader) ? $varyHeader . ', Origin' : 'Origin';
                $response->setHeader('Vary', $varyHeader);
                if ($this->configuration->shouldAllowCredentials()) {
                    $response->setHeader('Access-Control-Allow-Credentials', 'true');
                }
            }

            return $response;
        });
    }

    private function handleOptionRequest(Request $request) : Response {
        $response = new Response();
        $origin = $this->configuration->getOrigin();
        $corsMethod = $request->getHeader('Access-Control-Request-Method');
        $corsHeaders = $request->getHeader('Access-Control-Request-Headers');
        $corsHeaders = isset($corsHeaders) ? explode(',', $corsHeaders) : [];
        $allowedHeaders = $this->configuration->getAllowedHeaders();
        $allowedMethods = $this->configuration->getAllowedMethods();
        $badCorsHeaders = array_filter($corsHeaders, function($corsHeader) use($allowedHeaders) {
            return !in_array($corsHeader, $allowedHeaders);
        });
        if (($origin !== '*' && $request->getHeader('Origin') !== $origin) || !empty($badCorsHeaders)) {
            $response->setStatus(Status::FORBIDDEN);
        } else if (!in_array($corsMethod, $allowedMethods)) {
            $response->setStatus(Status::METHOD_NOT_ALLOWED);
        } else {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Access-Control-Allow-Methods', $this->turnArrayToHeaderString($allowedMethods));
            if (!empty($allowedHeaders)) {
                $response->setHeader('Access-Control-Allow-Headers', $this->turnArrayToHeaderString($allowedHeaders));
            }

            $exposableHeaders = $this->configuration->getExposableHeaders();
            if (!empty($exposableHeaders)) {
                $response->setHeader('Access-Control-Expose-Headers', $this->turnArrayToHeaderString($exposableHeaders));
            }

            if ($this->configuration->shouldAllowCredentials()) {
                $response->setHeader('Access-Control-Allow-Credentials', 'true');
            }
            $response->setHeader('Access-Control-Max-Age', $this->configuration->getMaxAge());
        }

        return $response;
    }

    private function turnArrayToHeaderString(array $headers) : string {
        return trim(implode(', ', $headers));
    }
}