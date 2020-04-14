<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Cors;

/**
 * A Configuration instance that will determine its values based on an array passed at object construction.
 *
 * @package Cspray\Labrador\Http\Cors
 */
final class ArrayConfiguration implements Configuration {

    private const REQUIRED_KEYS = ['origins'];

    private $configuration;

    /**
     * The $configuration array that has a set of expected keys within it.
     *
     * The following keys are supported by this implementation:
     *
     * - origins*
     * - allowed_methods*
     * - max_age
     * - allowed_headers
     * - exposable_headers
     * - allow_credentials
     *
     * * Indicates headers that are required to be provided. If they are not an exception will be thrown.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration) {
        $badKeys = $this->checkForBadKeys($configuration);
        if (!empty($badKeys)) {
            $msg = sprintf('An array with keys [%s] MUST be provided with non-empty values', implode(', ', $badKeys));
            throw new \InvalidArgumentException($msg);
        }
        $this->configuration = $configuration;
    }

    private function checkForBadKeys(array $configuration) : array {
        $badKeys = [];
        foreach (self::REQUIRED_KEYS as $requiredKey) {
            if (!isset($configuration[$requiredKey]) || empty($configuration[$requiredKey])) {
                $badKeys[] = $requiredKey;
            }
        }
        return $badKeys;
    }

    /**
     * @inheritDoc
     */
    public function getOrigins() : array {
        return $this->configuration['origins'];
    }

    /**
     * @inheritDoc
     */
    public function getMaxAge() : ?int {
        return $this->configuration['max_age'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getAllowedMethods() : array {
        return $this->configuration['allowed_methods'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getAllowedHeaders() : array {
        return $this->configuration['allowed_headers'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getExposableHeaders(): array {
        return $this->configuration['exposable_headers'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function shouldAllowCredentials(): bool {
        return $this->configuration['allow_credentials'] ?? false;
    }
}
