<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

use InvalidArgumentException;

/**
 * A fluent-API approach to building a Configuration implementation.
 *
 * @package Cspray\Labrador\Http\Cors
 */
class ConfigurationBuilder {

    private $origins;
    private $methods = [];
    private $allowedHeaders = [];
    private $exposableHeaders = [];
    private $maxAge;
    private $allowCredentials = false;

    private function __construct(array $origins) {
        $this->origins = $origins;
    }

    public static function forOrigins(string ...$origins) : ConfigurationBuilder {
        if (empty($origins)) {
            $msg = 'At least one Origin must be provided when building a Configuration';
            throw new InvalidArgumentException($msg);
        }
        return new ConfigurationBuilder($origins);
    }

    public function withMaxAge(int $maxAge) : self {
        $this->maxAge = $maxAge;
        return $this;
    }

    public function allowMethods(string ...$httpMethods) : self {
        $this->methods = $httpMethods;
        return $this;
    }

    public function allowRequestHeaders(string ...$headerNames) : self {
        $this->allowedHeaders = $headerNames;
        return $this;
    }

    public function exposeResponseHeaders(string ...$headerNames) : self {
        $this->exposableHeaders = $headerNames;
        return $this;
    }

    public function doAllowCredentials() : self {
        $this->allowCredentials = true;
        return $this;
    }

    public function doNotAllowCredentials() : self {
        $this->allowCredentials = false;
        return $this;
    }

    public function build() : Configuration {
        return new class(
            $this->origins,
            $this->methods,
            $this->maxAge,
            $this->allowedHeaders,
            $this->exposableHeaders,
            $this->allowCredentials) implements Configuration {
            private $origins;
            private $methods;
            private $maxAge;
            private $allowedHeaders;
            private $exposableHeaders;
            private $allowCredentials;

            public function __construct(
                array $origins,
                array $methods,
                ?int $maxAge,
                array $allowedHeaders,
                array $exposableHeaders,
                bool $allowCredentials
            ) {
                $this->origins = $origins;
                $this->methods = $methods;
                $this->maxAge = $maxAge;
                $this->allowedHeaders = $allowedHeaders;
                $this->exposableHeaders = $exposableHeaders;
                $this->allowCredentials = $allowCredentials;
            }

            /**
             * @inheritDoc
             */
            public function getOrigins(): array {
                return $this->origins;
            }

            /**
             * @inheritDoc
             */
            public function getMaxAge(): ?int {
                return $this->maxAge;
            }

            /**
             * @inheritDoc
             */
            public function getAllowedMethods(): array {
                return $this->methods;
            }

            /**
             * @inheritDoc
             */
            public function getAllowedHeaders(): array {
                return $this->allowedHeaders;
            }

            /**
             * @inheritDoc
             */
            public function getExposableHeaders(): array {
                return $this->exposableHeaders;
            }

            /**
             * @inheritDoc
             */
            public function shouldAllowCredentials(): bool {
                return $this->allowCredentials;
            }
        };
    }
}
