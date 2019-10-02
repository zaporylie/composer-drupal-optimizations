<?php

namespace zaporylie\ComposerDrupalOptimizations;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositoryManager;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;

class Plugin implements PluginInterface
{

    public function activate(Composer $composer, IOInterface $io)
    {
        // Set default version constraints based on the composer requirements.
        $extra = $composer->getPackage()->getExtra();
        $packages = $composer->getPackage()->getRequires();
        if (!isset($extra['composer-drupal-optimizations']['require'])) {
            $package = isset($packages['drupal/core']) ? $packages['drupal/core'] : (isset($packages['drupal/core-recommended']) ? $packages['drupal/core-recommended'] : null);
            if (isset($package)) {
                $coreConstraint = $package->getConstraint();
                $extra['composer-drupal-optimizations']['require'] = static::getDefaultRequire($coreConstraint);
                if (!empty($extra['composer-drupal-optimizations']['require']) && $io->isVerbose()) {
                  $io->write('Required tags were not explicitly set so the zaporylie/composer-drupal-optimizations set default based on project\'s composer.json content.');
                }
            }
        }
        if (!empty($extra['composer-drupal-optimizations']['require']) && $io->isVerbose()) {
            foreach ($extra['composer-drupal-optimizations']['require'] as $package => $version) {
                $io->write(sprintf('extra.commerce-drupal-optimizations.require.%s: \'%s\'', $package, $version));
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
                if ($repo instanceof TruncatedComposerRepository && !empty($extra['composer-drupal-optimizations']['require'])) {
                  $repo->setRequiredVersionConstraints($extra['composer-drupal-optimizations']['require']);
                }
            }
            $manager->setLocalRepository($this->getLocalRepository());
        }, $composer->getRepositoryManager(), RepositoryManager::class);
        $setRepositories($manager);
        $composer->setRepositoryManager($manager);
    }

    /**
     * Negotiates default require constraint and package for given drupal/core.
     *
     * @param \Composer\Semver\Constraint\ConstraintInterface
     *
     * @return array
     */
    static public function getDefaultRequire(ConstraintInterface $coreConstraint)
    {
        if ((new Constraint('>=', '8.5.0'))->matches($coreConstraint)
          && !(new Constraint('<', '8.5.0'))->matches($coreConstraint)) {
            return [
              'symfony/symfony' => '>3.4',
            ];
        }
        return [];
    }
}
