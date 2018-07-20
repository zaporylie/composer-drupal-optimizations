<?php

namespace zaporylie\ComposerDrupalOptimizations;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryManager;

class Plugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        // Set lowest-tags from composer.json, 'extra' section.
        if (($extra = $composer->getPackage()->getExtra()) && isset($extra['lowest-tags'])) {
            if (is_array($extra['lowest-tags'])) {
                Config::getInstance()->setLowestTags($extra['lowest-tags']);
            }
            elseif ($io->isVerbose()) {
                $io->writeError('Extra value "lowest-tags" was provided in wrong format - array expected.');
            }
        }

        $rfs = Factory::createRemoteFilesystem($io, $composer->getConfig());
        $manager = RepositoryFactory::manager($io, $composer->getConfig(), $composer->getEventDispatcher(), $rfs);
        $setRepositories = \Closure::bind(function (RepositoryManager $manager) {
            $manager->repositoryClasses = $this->repositoryClasses;
            $manager->setRepositoryClass('composer', TruncatedComposerRepository::class);
            $manager->repositories = $this->repositories;
            $i = 0;
            foreach (RepositoryFactory::defaultRepos(null, $this->config, $manager) as $repo) {
                $manager->repositories[$i++] = $repo;
            }
            $manager->setLocalRepository($this->getLocalRepository());
        }, $composer->getRepositoryManager(), RepositoryManager::class);
        $setRepositories($manager);
        $composer->setRepositoryManager($manager);
    }
}
