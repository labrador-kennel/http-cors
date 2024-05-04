# Labrador HTTP CORS

[![GitHub release](https://img.shields.io/github/release/labrador-kennel/http-cors.svg?style=flat-square)](https://github.com/labrador-kennel/http-cors/releases/latest)
[![GitHub license](https://img.shields.io/github/license/labrador-kennel/http-cors.svg?style=flat-square)](http://opensource.org/licenses/MIT)

A PHP 8+ library intended to provide spec-compliant CORS middleware for projects running on [Amp's http-server](https://amphp.org/http-server/). 
Though this library lives under the Labrador namespace it has only one dependency, `amphp/http-server`, and does not depend 
on any other Labrador packages.

## Installation

[Composer](https://getcomposer.org) is the only supported method for installing Labrador packages.

```
composer require cspray/labrador-http-cors
```

## Example

Below is an example using the fluent API. Please check out the documentation for more details and examples of non-fluent 
usage.

```php
<?php

use Cspray\Labrador\Http\Cors\ConfigurationBuilder;
use Cspray\Labrador\Http\Cors\SimpleConfigurationLoader;
use Cspray\Labrador\Http\Cors\CorsMiddleware;

$configuration = ConfigurationBuilder::forOrigins('https://example.com', 'https://foo.example.com')
    ->allowMethods('GET', 'POST', 'PUT', 'DELETE')
    ->withMaxAge(8600)
    ->allowRequestHeaders('X-Request-Header')
    ->exposeResponseHeaders('X-Response-Header')
    ->doAllowCredentials()
    ->build();
$loader = new SimpleConfigurationLoader($configuration);
$middleware = new CorsMiddleware($loader);
```

## Supported Versions

Only the 1.x release series is officially supported at this time. The previous 0.x release series will not see new 
features and will only see critical security fixes.

## Documentation

Labrador packages have thorough documentation in-repo in the `docs/` directory. You can also check out the documentation 
online at [https://labrador-kennel.io/docs/http-cors](https://labrador-kennel.io/docs/http-cors).

## Governance

All Labrador packages adhere to the rules laid out in the [Labrador Governance repo](https://github.com/labrador-kennel/governance).

