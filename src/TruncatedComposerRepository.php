<?php

namespace zaporylie\ComposerDrupalOptimizations;

use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Repository\ComposerRepository as BaseComposerRepository;
use Composer\Util\RemoteFilesystem;

class TruncatedComposerRepository extends BaseComposerRepository
{
    public function __construct(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null, RemoteFilesystem $rfs = null)
    {
        parent::__construct($repoConfig, $io, $config, $eventDispatcher, $rfs);
        $this->cache = new Cache($io, $config->get('cache-repo-dir').'/'.preg_replace('{[^a-z0-9.]}i', '-', $this->url), 'a-z0-9.$');
    }
    protected function fetchFile($filename, $cacheKey = null, $sha256 = null, $storeLastModifiedTime = false)
    {
        $data = parent::fetchFile($filename, $cacheKey, $sha256, $storeLastModifiedTime);
        return \is_array($data) ? $this->cache->removeLegacyTags($data) : $data;
    }
    public function setRequiredVersionConstraints(array $packages) {
        $this->cache->setRequiredVersionConstraints($packages);
    }
}
