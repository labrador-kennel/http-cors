<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;

use function Amp\call;

/**
 * An Amp http-server middleware that is responsible for handling CORS request.
 *
 * @package Cspray\Labrador\Http\Cors
 */
final class CorsMiddleware implements Middleware {

    private $configurationLoader;

    public function __construct(ConfigurationLoader $configurationLoader) {
        $this->configurationLoader = $configurationLoader;
    }

    /**
     * Will handle all OPTIONS Requests based on the Configuration for the given Request and ensure all non-OPTIONS
     * requests have appropriate CORS headers.
     *
     * @param Request $request
     * @param RequestHandler $requestHandler
     *
     * @return Promise<Response>
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler) : Promise {
        return call(function() use($request, $requestHandler) {
            if ($request->getMethod() === 'OPTIONS') {
                return $this->handleOptionRequest($request);
            }

            /** @var Response $response */
            $response = yield $requestHandler->handleRequest($request);

            if ($request->hasHeader('Origin')) {
                $configuration = $this->configurationLoader->loadConfiguration($request);
                $origins = $configuration->getOrigins();
                $hasWildCardOrigin = in_array('*', $origins, true);
                $originHeader = $request->getHeader('Origin');
                $originHeaderMatches = in_array($originHeader, $origins, true);
                $originResponseHeader = $hasWildCardOrigin ? '*' : $originHeader;
                if ($hasWildCardOrigin || $originHeaderMatches) {
                    $response->setHeader('Access-Control-Allow-Origin', $originResponseHeader);
                    $varyHeader = $response->getHeader('Vary');
                    $varyHeader = isset($varyHeader) ? $varyHeader . ', Origin' : 'Origin';
                    $response->setHeader('Vary', $varyHeader);
                    if ($configuration->shouldAllowCredentials()) {
                        $response->setHeader('Access-Control-Allow-Credentials', 'true');
                    }
                }
            }

            return $response;
        });
    }

    private function handleOptionRequest(Request $request) : Response {
        $configuration = $this->configurationLoader->loadConfiguration($request);
        $response = new Response();
        $origins = $configuration->getOrigins();
        $corsMethod = $request->getHeader('Access-Control-Request-Method');
        $corsHeaders = $request->getHeader('Access-Control-Request-Headers');
        $corsHeaders = isset($corsHeaders) ? explode(',', $corsHeaders) : [];
        $allowedHeaders = $configuration->getAllowedHeaders();
        $allowedMethods = $configuration->getAllowedMethods();
        $badCorsHeaders = array_filter($corsHeaders, function($corsHeader) use($allowedHeaders) {
            return !in_array($corsHeader, $allowedHeaders);
        });
        $originHeader = $request->getHeader('Origin');
        $hasWildCardOrigin = in_array('*', $origins, true);
        $originHeaderMatches = in_array($originHeader, $origins, true);
        $originResponseHeader = $hasWildCardOrigin ? '*' : $originHeader;

        if ((!$hasWildCardOrigin && !$originHeaderMatches) || !empty($badCorsHeaders)) {
            $response->setStatus(Status::FORBIDDEN);
        } elseif (!in_array($corsMethod, $allowedMethods)) {
            $response->setStatus(Status::METHOD_NOT_ALLOWED);
        } else {
            $response->setHeader('Access-Control-Allow-Origin', $originResponseHeader);
            $response->setHeader('Access-Control-Allow-Methods', $this->turnArrayToHeaderString($allowedMethods));
            if (!empty($allowedHeaders)) {
                $response->setHeader(
                    'Access-Control-Allow-Headers',
                    $this->turnArrayToHeaderString($allowedHeaders)
                );
            }

            $exposableHeaders = $configuration->getExposableHeaders();
            if (!empty($exposableHeaders)) {
                $response->setHeader(
                    'Access-Control-Expose-Headers',
                    $this->turnArrayToHeaderString($exposableHeaders)
                );
            }

            if ($configuration->shouldAllowCredentials()) {
                $response->setHeader('Access-Control-Allow-Credentials', 'true');
            }

            $maxAge = $configuration->getMaxAge();
            if (isset($maxAge)) {
                $response->setHeader('Access-Control-Max-Age', $configuration->getMaxAge());
            }
        }

        return $response;
    }

    private function turnArrayToHeaderString(array $headers) : string {
        return trim(implode(', ', $headers));
    }
}
