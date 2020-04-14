<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

use Amp\Http\Server\Request;

interface ConfigurationLoader {

    /**
     * @param Request $request
     * @return Configuration
     */
    public function loadConfiguration(Request $request) : Configuration;

}