<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

class ConfigurationBuilder {

    private function __construct(string $origin) {
    }

    public static function forOrigin(string $origin) : ConfigurationBuilder {
        return new ConfigurationBuilder($origin);
    }

    public function withMaxAge(int $maxAge) : self {
        return $this;
    }

    public function allowMethods(string ...$httpMethods) : self {
        return $this;
    }

    public function allowRequestHeaders(string ...$headerNames) : self {
        return $this;
    }

    public function exposeResponseHeaders(string ...$headerNames) : self {
        return $this;
    }

    public function doAllowCredentials() : self {
        return $this;
    }

    public function doNotAllowCredentials() : self {
        return $this;
    }

    public function build() : Configuration {
        // Should either reuse ArrayConfiguration or return an anonymous class.
    }



}