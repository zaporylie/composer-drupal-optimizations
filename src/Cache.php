<?php

namespace zaporylie\ComposerDrupalOptimizations;

use Composer\Cache as BaseCache;

/**
 * Class Cache
 * @package zaporylie\ComposerDrupalOptimizations
 */
class Cache extends BaseCache
{
    private static $lowestTags = [
        'symfony/symfony' => 'v3.4.0',
    ];

    public function read($file)
    {
        $content = parent::read($file);
        foreach (array_keys(self::$lowestTags) as $key) {
            list($provider, ) = explode('/', $key, 2);
            if (0 === strpos($file, "provider-$provider\$")) {
                $content = json_encode($this->removeLegacyTags(json_decode($content, true)));
                break;
            }
        }
        return $content;
    }

    public function removeLegacyTags(array $data)
    {
        foreach (self::$lowestTags as $package => $lowestVersion) {
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
}
