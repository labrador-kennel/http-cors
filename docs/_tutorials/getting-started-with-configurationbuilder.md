---
title: Getting Started with ConfigurationBuilder
---
Start off using CORS Middleware by using a fluent, type-safe API to generate your Configuration instance. If you can
store your configuration as PHP code this is the recommended way to create Configuration instances out-of-the-box.

Using `ConfigurationBuilder` is as simple as chaining together a series of fluent method calls and building your
`Configuration` instance.

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

The only method call required is `forOrigins`, otherwise the corresponding CORS header will not be set if 
there are no values provided. Once you have the `CorsMiddleware` instantiated you can attach it to your http-server 
router as appropriate for your application stack. If you are running [Labrador HTTP] you would attach this to the 
`Application` directly if you'd like to handle CORS requests similarly for all routes. You also have the option to 
attach this Middleware to a specific route or group of routes.
