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

    public function testRequiredKeysNotGivenThrowsException() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An array with keys [origin, allowed_methods] MUST be provided with non-empty values');

        new ArrayConfiguration([]);
    }

    public function testEmptyRequiredKeysGivenThrowsException() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An array with keys [origin, allowed_methods] MUST be provided with non-empty values');

        new ArrayConfiguration(['origin' => '', 'allowed_methods' => []]);
    }

    public function testDefaultMaxAgeValue() {
        $subject = new ArrayConfiguration(['origin' => 'https://example.com', 'allowed_methods' => ['GET']]);

        $this->assertNull($subject->getMaxAge());
    }

    public function testDefaultAllowedHeaders() {
        $subject = new ArrayConfiguration(['origin' => 'https://example.com', 'allowed_methods' => ['GET']]);

        $this->assertIsArray($subject->getAllowedHeaders());
        $this->assertEmpty($subject->getAllowedHeaders());
    }

    public function testDefaultExposableHeaders() {
        $subject = new ArrayConfiguration(['origin' => 'https://example.com', 'allowed_methods' => ['GET']]);

        $this->assertIsArray($subject->getExposableHeaders());
        $this->assertEmpty($subject->getExposableHeaders());
    }

    public function testDefaultShouldAllowCredentials() {
        $subject = new ArrayConfiguration(['origin' => 'https://example.com', 'allowed_methods' => ['GET']]);

        $this->assertFalse($subject->shouldAllowCredentials());
    }

}