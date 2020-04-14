<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

use Amp\Http\Server\Request;

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