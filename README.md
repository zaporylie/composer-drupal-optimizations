Optimize Composer for Drupal 8.5+ projects
====

# About

This composer-plugin contains set of tweaks that makes running composer in your Drupal project
significantly faster.

# Optimizations

- Reduce memory usage and CPU usage by removing legacy symfony tags (see also symfony/flex#378) 

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
