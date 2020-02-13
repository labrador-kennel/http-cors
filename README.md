# Labrador HTTP CORS

A PHP 7+ library intended to provide spec-compliant CORS middleware for projects running 
on [amphp http-server]. Though this library lives under the Labrador namespace it has only 
one dependency, `amphp/http-server`, and does not depend on any other Labrador packages.

## Installation

There is only 1 supported method for installing Labrador packages; [Composer].

```
composer require cspray/labrador-http-cors
```

## Usage

Using the Middleware is fairly simple; provide a `Configuration` instance that defines the 
CORS related data for a given Origin. Pass that config to a `CorsMiddleware` instance and then 
attach to the router layer you've implemented for your amphp application.

```php
<?php

require_once  __DIR__ . '/vendor/autoload.php';

use Cspray\Labrador\Http\Cors\ArrayConfiguration;
use Cspray\Labrador\Http\Cors\CorsMiddleware;

$config = new ArrayConfiguration([
    'origin' => 'https://allowed-domain.example.com',
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'X-Request-Header'],
    'exposable_headers' => ['X-Response-Header'],
    'allow_credentials' => true
]);
$middleware = new CorsMiddleware($config);

// Attaching to your routing layer is an exercise left to the reader

// In Labrador applications you would typically attach this directly on the Application
// to catch preflight requests at all endpoints. Obviously if your solution requires 
// more find-grained handling you can always add the Middleware to a specific route on
// your Router instance.
```

## Security Issues

This library aims to be well tested and spec-compliant. If you encounter a security issue 
with this library please email cspray+security@gmail.com instead of posting an issue directly 
on this repository.

[amphp http-server]: https://amphp.org/http-server/
[Composer]: https://getcomposer.org
