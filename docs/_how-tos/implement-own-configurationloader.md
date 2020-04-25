---
title: Implement your own ConfigurationLoader
---
Out-of-the-box this library provides a single `ConfigurationLoader` that will always return a single Configuration
for all requests. If you need something more complex this guide details how to create your own `ConfigurationLoader`
instance.

In our example we're going to create a `ConfigurationLoader` that allows different `Configuration` to be used based on 
the domain of the Request. We assume in our example that you're running a multi-tenant web app that can receive 
requests from many domains. We are going to put our code under the `Acme\Http\Cors` namespace, you should change 
this to match the appropriate namespace for your project.

#### 1. Create your new class

The first step is to create your new class and implement the `ConfigurationLoader` interface; don't worry that the 
method doesn't return anything yet... we'll complete that later in this guide.

```php
<?php

namespace Acme\Http\Cors;

use Amp\Http\Server\Request;
use Cspray\Labrador\Http\Cors\Configuration;
use Cspray\Labrador\Http\Cors\ConfigurationLoader;

final class MultiTenantConfigurationLoader implements ConfigurationLoader {

    public function loadConfiguration(Request $request) : Configuration {

    }

}
```

#### 2. Add methods to store tenant specific Configuration

Next we need to add a way to specify which Configuration each tenant receives. Since this is your code and called by you 
on the middleware setup you can design whatever system is best for your application. In our example we have simply added 
a method that will accept a tenant's domain and the Configuration associated with it.

```php
<?php

namespace Acme\Http\Cors;

use Amp\Http\Server\Request;
use Cspray\Labrador\Http\Cors\Configuration;
use Cspray\Labrador\Http\Cors\ConfigurationLoader;

final class MultiTenantConfigurationLoader implements ConfigurationLoader {

    private $tenantConfigs = [];

    public function addTenantConfiguration(string $tenantDomain, Configuration $configuration) : void {
        $this->tenantConfigs[$tenantDomain] = $configuration;
    }

    public function loadConfiguration(Request $request) : Configuration {

    }

}
```

#### 3. Return Configuration based on Request host


```php
<?php

namespace Acme\Http\Cors;

use Amp\Http\Server\Request;
use Cspray\Labrador\Http\Cors\Configuration;
use Cspray\Labrador\Http\Cors\ConfigurationLoader;

final class MultiTenantConfigurationLoader implements ConfigurationLoader {

    private $tenantConfigs = [];

    public function addTenantConfiguration(string $tenantDomain, Configuration $configuration) : void {
        $this->tenantConfigs[$tenantDomain] = $configuration;
    }

    public function loadConfiguration(Request $request) : Configuration {
        $host = $request->getUri()->getHost();
        $configuration = $this->tenantConfigs[$host] ?? null;
        return $configuration;
    }

}
```

#### 4. Handle no Configuration for Request

If you've added logic to have many types of Configurations it is likely you may encounter a Request that does not match 
that logic and you cannot reliably determine a Configuration for. Based on your application's needs you will need to 
determine how to handle this scenario to fix the Null Pointer Error that can occur in code from step 3.

##### Option 1: Return a default Configuration instance

The recommended way to deal with this scenario is to provide a default Configuration instance specific for the requested 
Origin. 

```php
<?php

namespace Acme\Http\Cors;

use Amp\Http\Server\Request;
use Cspray\Labrador\Http\Cors\Configuration;
use Cspray\Labrador\Http\Cors\ConfigurationBuilder;
use Cspray\Labrador\Http\Cors\ConfigurationLoader;

final class MultiTenantConfigurationLoader implements ConfigurationLoader {

    private $tenantConfigs = [];

    public function addTenantConfiguration(string $tenantDomain, Configuration $configuration) : void {
        $this->tenantConfigs[$tenantDomain] = $configuration;
    }

    public function loadConfiguration(Request $request) : Configuration {
        $host = $request->getUri()->getHost();
        $configuration = $this->tenantConfigs[$host] ?? null;
        if ($configuration === null) {
            $origin = $request->getHeader('Origin');
            $configuration = ConfigurationBuilder::forOrigins($origin)->build();
        }
        return $configuration;
    }

}
```

##### Option 2: Throw an Exception

If getting a Request from a domain that you haven't configured for CORS really is an exceptional situation with no 
reasonable way to handle it you can throw an exception. 

```php
<?php

namespace Acme\Http\Cors;

use Amp\Http\Server\Request;
use Cspray\Labrador\Http\Cors\Configuration;
use Cspray\Labrador\Http\Cors\ConfigurationLoader;

final class MultiTenantConfigurationLoader implements ConfigurationLoader {

    private $tenantConfigs = [];

    public function addTenantConfiguration(string $tenantDomain, Configuration $configuration) : void {
        $this->tenantConfigs[$tenantDomain] = $configuration;
    }

    public function loadConfiguration(Request $request) : Configuration {
        $host = $request->getUri()->getHost();
        $configuration = $this->tenantConfigs[$host] ?? null;
        if ($configuration === null) {
            throw new \InvalidArgumentException(sprintf('No Configuration for host: %s', $host));
        }   
        return $configuration;
    }

}
```

