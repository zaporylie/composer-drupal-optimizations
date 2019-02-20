<?php

namespace zaporylie\ComposerDrupalOptimizations\Tests;

use Composer\IO\NullIO;
use PHPUnit\Framework\TestCase;
use zaporylie\ComposerDrupalOptimizations\Cache;

class CacheTest extends TestCase
{

    /**
     * Tests if data is not malformed and only valid array from valid provider
     * is processed.
     *
     * @param $provided
     * @param $expected
     *
     * @dataProvider provideReadTest
     * @covers \zaporylie\ComposerDrupalOptimizations\Cache::read
     */
    public function testRead($provided, $expected)
    {
        $cache = new class(new NullIO(), 'test') extends Cache {
            protected $packages = [
              'vendor/package' => 'version',
            ];
            protected function readFile($file)
            {
                // Remove provider - used only for tests.
                if (0 === strpos($file, 'provider-vendor$')) {
                    $file = substr($file, 16);
                }
                return $file;
            }
            public function removeLegacyTags(array $data)
            {
                $data['status'] = 'ok';
                return $data;
            }
        };
        static::assertEquals($expected, $cache->read($provided));
    }

    /**
     * Test data.
     */
    function provideReadTest()
    {
        yield 'normal' => ['{"a":"b"}', '{"a":"b"}'];
        yield 'falsy' => ['{"a":"b"', '{"a":"b"'];
        yield 'empty' => ['', ''];
        yield 'matching-incorrect-provider' => ['{"provider":"vendor"}', '{"provider":"vendor"}'];
        yield 'matching' => ['provider-vendor${"provider":"vendor"}', '{"provider":"vendor","status":"ok"}'];
    }

    /**
     * @dataProvider provideRemoveLegacyTags
     * @covers \zaporylie\ComposerDrupalOptimizations\Cache::removeLegacyTags
     */
    public function testRemoveLegacyTags(array $expected, array $packages, array $versionConstraints)
    {
        /** @var Cache $cache */
        $cache = (new \ReflectionClass(Cache::class))->newInstanceWithoutConstructor();
        $cache->setRequiredVersionConstraints($versionConstraints);
        $this->assertSame(['packages' => $expected], $cache->removeLegacyTags(['packages' => $packages]));
    }

    /**
     * Test data.
     */
    public function provideRemoveLegacyTags()
    {
        yield 'no-symfony/symfony' => [[123], [123], ['symfony/symfony' => '~1']];
        $branchAlias = function ($versionAlias) {
            return [
              'extra' => [
                'branch-alias' => [
                  'dev-master' => $versionAlias.'-dev',
                ],
              ],
            ];
        };
        $packages = [
          'foo/unrelated' => [
            '1.0.0' => [],
          ],
          'symfony/symfony' => [
            '3.3.0' => [
              'version_normalized' => '3.3.0.0',
              'replace' => ['symfony/foo' => 'self.version'],
            ],
            '3.4.0' => [
              'version_normalized' => '3.4.0.0',
              'replace' => ['symfony/foo' => 'self.version'],
            ],
            'dev-master' => $branchAlias('3.5') + [
                'replace' => ['symfony/foo' => 'self.version'],
              ],
          ],
          'symfony/foo' => [
            '3.3.0' => ['version_normalized' => '3.3.0.0'],
            '3.4.0' => ['version_normalized' => '3.4.0.0'],
            'dev-master' => $branchAlias('3.5'),
          ],
        ];
        yield 'empty-intersection-ignores' => [$packages, $packages, ['symfony/symfony' => '~2.0']];
        yield 'empty-intersection-ignores' => [$packages, $packages, ['symfony/symfony' => '~4.0']];
        $expected = $packages;
        unset($expected['symfony/symfony']['3.3.0']);
        unset($expected['symfony/foo']['3.3.0']);
        yield 'non-empty-intersection-filters' => [$expected, $packages, ['symfony/symfony' => '~3.4']];
        unset($expected['symfony/symfony']['3.4.0']);
        unset($expected['symfony/foo']['3.4.0']);
        yield 'master-only' => [$expected, $packages, ['symfony/symfony' => '~3.5']];
        $packages = [
          'symfony/symfony' => [
            '2.8.0' => [
              'version_normalized' => '2.8.0.0',
              'replace' => [
                'symfony/legacy' => 'self.version',
                'symfony/foo' => 'self.version',
              ],
            ],
          ],
          'symfony/legacy' => [
            '2.8.0' => ['version_normalized' => '2.8.0.0'],
            'dev-master' => $branchAlias('2.8'),
          ],
        ];
        yield 'legacy-are-not-filtered' => [$packages, $packages, ['symfony/symfony' => '~3.0']];
        $packages = [
          'symfony/symfony' => [
            '2.8.0' => [
              'version_normalized' => '2.8.0.0',
              'replace' => [
                'symfony/foo' => 'self.version',
                'symfony/new' => 'self.version',
              ],
            ],
            'dev-master' => $branchAlias('3.0') + [
                'replace' => [
                  'symfony/foo' => 'self.version',
                  'symfony/new' => 'self.version',
                ],
              ],
          ],
          'symfony/foo' => [
            '2.8.0' => ['version_normalized' => '2.8.0.0'],
            'dev-master' => $branchAlias('3.0'),
          ],
          'symfony/new' => [
            'dev-master' => $branchAlias('3.0'),
          ],
        ];
        $expected = $packages;
        unset($expected['symfony/symfony']['dev-master']);
        unset($expected['symfony/foo']['dev-master']);
        yield 'master-is-filtered-only-when-in-range' => [$expected, $packages, ['symfony/symfony' => '~2.8']];
    }
}
