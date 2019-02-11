<?php

namespace zaporylie\ComposerDrupalOptimizations;

use Composer\Cache as BaseCache;
use Composer\IO\IOInterface;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use Composer\Util\Filesystem;

/**
 * Class Cache
 * @package zaporylie\ComposerDrupalOptimizations
 */
class Cache extends BaseCache
{

    /**
     * @var array
     */
    protected $packages = [];

    /**
     * @var \Composer\Semver\VersionParser
     */
    protected $versionParser;

    public function __construct(IOInterface $io, $cacheDir, $whitelist = 'a-z0-9.', Filesystem $filesystem = null) {
        parent::__construct($io, $cacheDir, $whitelist, $filesystem);
        $this->versionParser = new VersionParser();
    }

    /**
     * {@inheritdoc}
     */
    public function read($file)
    {
        $content = parent::read($file);
        foreach (array_keys($this->packages) as $key) {
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
        // Skip if the list of packages is empty.
        if (!$this->packages || empty($data['packages'])) {
            return $data;
        }

        // Skip if none of the packages was found.
        if (!array_diff_key($data['packages'], $this->packages)) {
            return $data;
        }

        foreach ($this->packages as $packageName => $packageVersionConstraint) {
            if (!isset($data['packages'][$packageName])) {
                continue;
            }
            $packages = [];
            $specificPackage = $data['packages'][$packageName];
            foreach ($specificPackage as $version => $composerJson) {
                if ('dev-master' === $version) {
                    $normalizedVersion = $this->versionParser->normalize($composerJson['extra']['branch-alias']['dev-master']);
                } else {
                    $normalizedVersion = $composerJson['version_normalized'];
                }
                if (!$this->versionParser->parseConstraints($packageVersionConstraint)->matches(new Constraint('==',
                  $normalizedVersion))) {
                    $packages += isset($composerJson['replace']) ? $composerJson['replace'] : [];
                } else {
                    unset($specificPackage[$version]);
                }
            }

            // Ignore requirements: their intersection with versions of the package gives empty result.
            if (!$specificPackage) {
                continue;
            }
            $data['packages'][$packageName] = $specificPackage;

            unset($specificPackage['dev-master']);
            foreach ($data['packages'] as $name => $versions) {
                if (!isset($packages[$name]) || null === $devMasterAlias = (isset($versions['dev-master']['extra']['branch-alias']['dev-master']) ? $versions['dev-master']['extra']['branch-alias']['dev-master'] : null)) {
                    continue;
                }
                $devMaster = $versions['dev-master'];
                $versions = array_intersect_key($versions, $specificPackage);
                if (!$this->versionParser->parseConstraints($packageVersionConstraint)->matches(new Constraint('==',
                  $this->versionParser->normalize($devMasterAlias)))) {
                    $versions['dev-master'] = $devMaster;
                }
                if ($versions) {
                    $data['packages'][$name] = $versions;
                }
            }
        }
        return $data;

    }

    /**
     * @param array $packages
     *
     * @return $this
     */
    public function setLegacyVersionConstraints(array $packages) {
      $this->packages = $packages;
      return $this;
    }

}
