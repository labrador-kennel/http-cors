<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

use Amp\Http\Server\Request;

/**
 * Responsible for ensuring that the appropriate Configuration is used for a given Request.
 *
 * @package Cspray\Labrador\Http\Cors
 */
interface ConfigurationLoader {

    /**
     * Based on whatever criteria is appropriate from the Request object return an appropriate Configuration object
     * that will handle CORS settings for the Response.
     *
     * @param Request $request
     * @return Configuration
     */
    public function loadConfiguration(Request $request) : Configuration;
}
