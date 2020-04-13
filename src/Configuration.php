<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

interface Configuration {

    /**
     * Return the Origin that this CORS Configuration is valid for.
     *
     * If the Origin header in the OPTIONS request matches this value the rest of this Configuration will determine
     *the headers that are returned. If no Configuration is present for the Origin is not allowed.
     *
     * @return string[]
     */
    public function getOrigins() : array;

    /**
     * Return the number of seconds that the browser should cache these cross-origin headers.
     *
     * @return int
     */
    public function getMaxAge() : ?int;

    /**
     * Return the HTTP methods that are supported for cross-origin requests with this origin.
     *
     * @return string[]
     */
    public function getAllowedMethods() : array;

    /**
     * Return the HTTP headers that a Request is allowed to send cross-origin..
     *
     * @return array
     */
    public function getAllowedHeaders() : array;

    /**
     * Return a list of custom header names that can be exposed by the server in Responses that cross-origin requests
     * should have access to.
     *
     * @return string[]
     */
    public function getExposableHeaders() : array;

    /**
     * Return true or false for whether this cross-origin request should interact with Cookies.
     *
     * @return bool
     */
    public function shouldAllowCredentials() : bool;

}