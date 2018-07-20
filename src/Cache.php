<?php

namespace zaporylie\ComposerDrupalOptimizations;

use Composer\Cache as BaseCache;

/**
 * Class Cache
 * @package zaporylie\ComposerDrupalOptimizations
 */
class Cache extends BaseCache
{

    /**
     * {@inheritdoc}
     */
    public function read($file)
    {
        $content = parent::read($file);
        foreach (array_keys($this->getLowestTags()) as $key) {
            list($provider, ) = explode('/', $key, 2);
            if (0 === strpos($file, "provider-$provider\$")) {
                $content = json_encode($this->removeLegacyTags(json_decode($content, true)));
                break;
            }
        }
        return $content;
    }

    /**
     * Removes legacy tags from $data.
     *
     * @param array $data
     * @return array
     */
    public function removeLegacyTags(array $data)
    {
        foreach ($this->getLowestTags() as $package => $lowestVersion) {
            if (!isset($data['packages'][$package][$lowestVersion])) {
                continue;
            }
            foreach ($data['packages'] as $package => $versions) {
                foreach ($versions as $version => $composerJson) {
                    if (version_compare($version, $lowestVersion, '<')) {
                        unset($data['packages'][$package][$version]);
                    }
                }
            }
            break;
        }
        return $data;
    }

    /**
     * Gets list of lowest allowed tags.
     *
     * @return array
     */
    private function getLowestTags()
    {
        return Config::getInstance()->getLowestTags();
    }
}
