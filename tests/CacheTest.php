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
     */
    public function testRead($provided, $expected)
    {
        $cache = new class(new NullIO(), 'test') extends Cache {
            protected static $lowestTags = [
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
}
