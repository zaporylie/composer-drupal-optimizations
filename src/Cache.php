<?php

namespace zaporylie\ComposerDrupalOptimizations;

use Composer\Cache as BaseCache;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;

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

    /**
     * {@inheritdoc}
     */
    public function read($file)
    {
        $content = $this->readFile($file);
        if (!\is_array($data = json_decode($content, true))) {
            return $content;
        }
        foreach (array_keys($this->packages) as $key) {
            list($provider, ) = explode('/', $key, 2);
            if (0 === strpos($file, "provider-$provider\$")) {
                $data = $this->removeLegacyTags($data);
                break;
            }
        }
        return json_encode($data);
    }

    protected function readFile($file)
    {
        return parent::read($file);
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
                if (null !== $alias = (isset($composerJson['extra']['branch-alias'][$version]) ? $composerJson['extra']['branch-alias'][$version] : null)) {
                    $normalizedVersion = $this->versionParser->normalize($alias);
                } elseif (null === $normalizedVersion = isset($composerJson['version_normalized']) ? $composerJson['version_normalized'] : null) {
                    continue;
                }
                $packageConstraint = $this->versionParser->parseConstraints($packageVersionConstraint);
                $versionConstraint = new Constraint('==', $normalizedVersion);
                if ($packageConstraint->matches($versionConstraint)) {
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
                $packageConstraint = $this->versionParser->parseConstraints($packageVersionConstraint);
                $versionConstraint = new Constraint('==', $this->versionParser->normalize($devMasterAlias));
                if ($packageConstraint->matches($versionConstraint)) {
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
    public function setRequiredVersionConstraints(array $packages) {
        $this->versionParser = new VersionParser();
        $this->packages = $packages;
        return $this;
    }

}
