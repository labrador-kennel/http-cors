<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

use Amp\Http\Server\Request;

/**
 * A ConfigurationLoader that will simply return whatever Configuration is passed to it.
 *
 * @package Cspray\Labrador\Http\Cors
 */
final class SimpleConfigurationLoader implements ConfigurationLoader {

    private $configuration;

    public function __construct(Configuration $configuration) {
        $this->configuration = $configuration;
    }

    /**
     * @inheritDoc
     */
    public function loadConfiguration(Request $request) : Configuration {
        return $this->configuration;
    }
}
