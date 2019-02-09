<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors\Test;

use Cspray\Labrador\Http\Cors\ArrayConfiguration;
use PHPUnit\Framework\TestCase;

class ArrayConfigurationTest extends TestCase {

    private function configurationFixture() : array {
        return [
            'origin' => 'http://example.com',
            'max_age' => 3000,
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'allowed_headers' => ['Content-Type', 'X-Custom-Req-Header'],
            'exposable_headers' => ['X-Custom-Res-Header'],
            'allow_credentials' => true
        ];
    }

    private function subject() : ArrayConfiguration {
        return new ArrayConfiguration($this->configurationFixture());
    }

    public function testGetOrigin() {
        $this->assertSame('http://example.com', $this->subject()->getOrigin());
    }

    public function testGetMaxAge() {
        $this->assertSame(3000, $this->subject()->getMaxAge());
    }

    public function testGetAllowedMethods() {
        $this->assertSame(['GET', 'POST', 'PUT', 'DELETE'], $this->subject()->getAllowedMethods());
    }

    public function testGetAllowedHeaders() {
        $this->assertSame(['Content-Type', 'X-Custom-Req-Header'], $this->subject()->getAllowedHeaders());
    }

    public function testGetExposableHeaders() {
        $this->assertSame(['X-Custom-Res-Header'], $this->subject()->getExposableHeaders());
    }

    public function testShouldAllowCredentials() {
        $this->assertTrue($this->subject()->shouldAllowCredentials());
    }

}