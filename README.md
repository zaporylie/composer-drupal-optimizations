Optimize Composer for Drupal 8.5+ projects
====
[![Build Status](https://travis-ci.org/zaporylie/composer-drupal-optimizations.svg?branch=master)](https://travis-ci.org/zaporylie/composer-drupal-optimizations)
![Packagist](https://img.shields.io/packagist/v/zaporylie/composer-drupal-optimizations.svg)


# About

This composer-plugin contains set of improvements that makes running `composer update` on your Drupal project
significantly faster.

# Installation

```bash
composer require zaporylie/composer-drupal-optimizations:^1.0
```

# Optimizations

- Reduce memory usage and CPU usage by removing legacy symfony tags (see also https://github.com/symfony/flex/pull/378) 

(only one at the moment)

# Benchmark

Following numbers are for clean https://github.com/drupal-composer/drupal-project/ without and with this plugin.

Before:

```
Memory usage: 304.16MB (peak: 876.79MB), time: 17.13s
```

After:

```
Memory usage: 218.72MB (peak: 250.44MB), time: 4.83s
```

# Credits

- Symfony community - idea and development; Special thanks to @nicolas-grekas
- Jakub Piasecki - port and maintenance
