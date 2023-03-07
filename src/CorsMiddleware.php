<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

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
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handleOptionRequest($request);
        }

        $response = $requestHandler->handleRequest($request);

        if ($request->hasHeader('Origin')) {
            $configuration = $this->configurationLoader->loadConfiguration($request);
            if ($this->doesOriginHeaderMatch($request, $configuration)) {
                $originResponseHeader = $this->getOriginResponseHeader($request, $configuration);
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
    }

    private function handleOptionRequest(Request $request) : Response {
        $configuration = $this->configurationLoader->loadConfiguration($request);
        $response = new Response();
        $corsMethod = $request->getHeader('Access-Control-Request-Method');
        $corsHeaders = $request->getHeader('Access-Control-Request-Headers');
        $corsHeaders = isset($corsHeaders) ? explode(',', $corsHeaders) : [];
        $corsHeaders = array_map('trim', $corsHeaders);
        $allowedHeaders = $configuration->getAllowedHeaders();
        $allowedMethods = $configuration->getAllowedMethods();
        $normalizedAllowedHeaders = array_map('strtolower', $allowedHeaders);
        $badCorsHeaders = array_filter($corsHeaders, function($corsHeader) use($normalizedAllowedHeaders) {
            return !in_array(strtolower($corsHeader), $normalizedAllowedHeaders, true);
        });

        if (!$this->doesOriginHeaderMatch($request, $configuration) || !empty($badCorsHeaders)) {
            $response->setStatus(HttpStatus::FORBIDDEN);
        } elseif (!in_array($corsMethod, $allowedMethods)) {
            $response->setStatus(HttpStatus::METHOD_NOT_ALLOWED);
        } else {
            $originResponseHeader = $this->getOriginResponseHeader($request, $configuration);
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
                $response->setHeader('Access-Control-Max-Age', (string) $maxAge);
            }
        }

        return $response;
    }

    private function doesOriginHeaderMatch(Request $request, Configuration $configuration) : bool {
        $origins = $configuration->getOrigins();
        $hasWildCardOrigin = in_array('*', $origins, true);
        if ($hasWildCardOrigin) {
            return true;
        }

        $originHeader = strtolower($request->getHeader('Origin'));
        $normalizedOrigins = array_map('strtolower', $origins);
        return in_array($originHeader, $normalizedOrigins, true);
    }


    private function getOriginResponseHeader(Request $request, Configuration $configuration) : string {
        $origins = $configuration->getOrigins();
        $hasWildCardOrigin = in_array('*', $origins, true);
        return $hasWildCardOrigin ? '*' : strtolower($request->getHeader('Origin'));
    }

    private function turnArrayToHeaderString(array $headers) : string {
        return trim(implode(', ', $headers));
    }
}
