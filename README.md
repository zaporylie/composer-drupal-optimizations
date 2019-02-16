Optimize Composer for Drupal projects
====
[![Build Status](https://travis-ci.org/zaporylie/composer-drupal-optimizations.svg?branch=master)](https://travis-ci.org/zaporylie/composer-drupal-optimizations)
![Packagist](https://img.shields.io/packagist/v/zaporylie/composer-drupal-optimizations.svg)


# About

This composer-plugin contains set of improvements that makes running heavy duty composer commands (i.e. `composer update` or `composer require`) much faster.

# Installation

```bash
composer require zaporylie/composer-drupal-optimizations:^1.0
```

No configuration required 🎊

# Optimizations

- Reduce memory usage and CPU usage by removing legacy symfony tags (see also https://github.com/symfony/flex/pull/378) 

(only one at the moment)

# Benchmark

Following numbers are for clean https://github.com/drupal-composer/drupal-project/ without and with this plugin.

Before:

```
Memory usage: 320.49MB (peak: 1092.58MB), time: 21.01s
```

After:

```
Memory usage: 238.79MB (peak: 302.82MB), time: 3.57s
```

# Configuration

If no configuration is provided this package will provide sensible defaults based on the content of project's composer.json
file. Default configuration should cover 99% of the cases. However, in case you want to manually specify the legacy tags
that should be filtered out you are welcome to use the `extra` section:

```json
{
  "extra": {
    "composer-drupal-optimizations": {
      "legacy": {
        "symfony/symfony": "<3.4"
      }
    }
  }
}
```

Please note that the version constraint is reversed compared to the constraints in require section of composer.json file.
`<3.4` basically means that all versions that are lower than 3.4 should be skipped, including `1.0`, `2.7` `3.3` or `2.x-dev`.

***Recommendation note:***
Use defaults (skip config above) if possible - this package will be maintained throughout the Drupal's lifecycle in order
to optimize legacy constraints in parallel with Drupal's requirements.

# Credits

- Symfony community - idea and development; Special thanks to @nicolas-grekas
- Jakub Piasecki - port and maintenance
