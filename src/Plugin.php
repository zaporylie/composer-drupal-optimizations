<?php

namespace zaporylie\ComposerDrupalOptimizations;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryManager;
use Composer\Semver\Semver;

class Plugin implements PluginInterface
{

    public function activate(Composer $composer, IOInterface $io)
    {
        // Set default version constraints based on the composer requirements.
        $extra = $composer->getPackage()->getExtra();
        $packages = $composer->getPackage()->getRequires();
        if (empty($extra['composer-drupal-optimizations']['legacy']) && isset($packages['drupal/core'])) {
            for ($i = 0; $i < 10; $i++) {
                if (Semver::satisfies(sprintf("8.%d", $i), $packages['drupal/core']->getConstraint()->getPrettyString())) {
                    break;
                }
            }
            if ($i >= 5) {
                $extra['composer-drupal-optimizations']['legacy'] = [
                    'symfony/symfony' => '<3.4',
                ];
                if ($io->isVeryVerbose() || TRUE) {
                    $io->write('Legacy tags were not explicitly set so the zaporylie/composer-drupal-optimizations did the calculation:');
                    foreach ($extra['composer-drupal-optimizations']['legacy'] as $package => $version) {
                        $io->write(sprintf('extra.commerce-drupal-optimizations.legacy.%s: \'%s\'', $package, $version));
                    }
                }
            }
        }

        $rfs = Factory::createRemoteFilesystem($io, $composer->getConfig());
        $manager = RepositoryFactory::manager($io, $composer->getConfig(), $composer->getEventDispatcher(), $rfs);
        $setRepositories = \Closure::bind(function (RepositoryManager $manager) use ($extra) {
            $manager->repositoryClasses = $this->repositoryClasses;
            $manager->setRepositoryClass('composer', TruncatedComposerRepository::class);
            $manager->repositories = $this->repositories;
            $i = 0;
            foreach (RepositoryFactory::defaultRepos(null, $this->config, $manager) as $repo) {
                $manager->repositories[$i++] = $repo;
                if ($repo instanceof TruncatedComposerRepository && isset($extra['composer-drupal-optimizations']['legacy'])) {
                  $repo->setLegacyVersionConstraints($extra['composer-drupal-optimizations']['legacy']);
                }
            }
            $manager->setLocalRepository($this->getLocalRepository());
        }, $composer->getRepositoryManager(), RepositoryManager::class);
        $setRepositories($manager);
        $composer->setRepositoryManager($manager);
    }
}
