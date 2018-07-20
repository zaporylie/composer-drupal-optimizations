<?php

namespace zaporylie\ComposerDrupalOptimizations;

/**
 * Class Config
 * @package zaporylie\ComposerDrupalOptimizations
 * @internal
 * @todo: Replace with DI.
 */
class Config {
    /**
     * @var Config
     */
    private static $instance;

    /**
     * @var array
     */
    private $lowestTags = [
        'symfony/symfony' => 'v3.4.0',
    ];

    /**
     * @return Config
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function __construct() {}

    private function __clone() {}

    private function __wakeup() {}

    /**
     * Sets list of lowest allowed tags.
     *
     * @param array $lowestTags
     * @return $this
     */
    public function setLowestTags(array $lowestTags) {
        $this->lowestTags = $lowestTags;
        return $this;
    }

    /**
     * Gets array of lowest allowed tags.
     *
     * @return array
     */
    public function getLowestTags() {
        return $this->lowestTags;
    }
}
