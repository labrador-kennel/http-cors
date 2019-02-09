<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors\Test;

use Amp\Loop;
use PHPUnit\Framework\TestCase;
use function Amp\call;
use function Amp\Promise\wait;

class AsyncTestCase extends TestCase {

    private $timeoutId;

    public function runTest() {
        $returnValue = wait(call(function() {
            return parent::runTest();
        }));
        if (isset($this->timeoutId)) {
            Loop::cancel($this->timeoutId);
        }
        return $returnValue;
    }

    protected function timeout(int $timeout) {
        $this->timeoutId = Loop::delay($timeout, function() use($timeout) {
            Loop::stop();
            $this->fail('Expected test to complete before ' . $timeout . 'ms time limit');
        });
    }
}