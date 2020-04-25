<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

use PHPUnit\Framework\TestCase;

class ConfigurationBuilderTest extends TestCase {

    public function testNoOriginsThrowsError() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one Origin must be provided when building a Configuration');

        ConfigurationBuilder::forOrigins();
    }

    public function testBuildCompleteConfiguration() {
        $configuration = ConfigurationBuilder::forOrigins('https://example.com')
            ->allowMethods('GET', 'POST', 'DELETE')
            ->allowRequestHeaders('X-Request-Header')
            ->exposeResponseHeaders('X-Response-Header')
            ->doAllowCredentials()
            ->withMaxAge(1000)
            ->build();

        $this->assertSame(['https://example.com'], $configuration->getOrigins());
        $this->assertSame(['GET', 'POST', 'DELETE'], $configuration->getAllowedMethods());
        $this->assertSame(['X-Request-Header'], $configuration->getAllowedHeaders());
        $this->assertSame(['X-Response-Header'], $configuration->getExposableHeaders());
        $this->assertSame(1000, $configuration->getMaxAge());
        $this->assertTrue($configuration->shouldAllowCredentials());
    }

    public function testBuildMinimalConfigurationWithDefaultValues() {
        $configuration = ConfigurationBuilder::forOrigins('https://example.com', 'https://foo.com')->build();

        $this->assertSame(['https://example.com', 'https://foo.com'], $configuration->getOrigins());
        $this->assertSame([], $configuration->getAllowedMethods());
        $this->assertSame([], $configuration->getAllowedHeaders());
        $this->assertSame([], $configuration->getExposableHeaders());
        $this->assertNull($configuration->getMaxAge());
        $this->assertFalse($configuration->shouldAllowCredentials());
    }

    public function testDoNowAllowCredentials() {
        $configuration = ConfigurationBuilder::forOrigins('https://example.com', 'https://foo.com')
            ->allowMethods('GET', 'POST', 'DELETE')
            ->doAllowCredentials()      // want to make sure we get something other than the default value
            ->doNotAllowCredentials()
            ->build();

        $this->assertSame(['https://example.com', 'https://foo.com'], $configuration->getOrigins());
        $this->assertSame(['GET', 'POST', 'DELETE'], $configuration->getAllowedMethods());
        $this->assertSame([], $configuration->getAllowedHeaders());
        $this->assertSame([], $configuration->getExposableHeaders());
        $this->assertNull($configuration->getMaxAge());
        $this->assertFalse($configuration->shouldAllowCredentials());
    }
}
