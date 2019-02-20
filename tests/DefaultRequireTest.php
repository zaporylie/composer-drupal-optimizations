<?php

namespace zaporylie\ComposerDrupalOptimizations\Tests;

use Composer\Semver\VersionParser;
use PHPUnit\Framework\TestCase;
use zaporylie\ComposerDrupalOptimizations\Plugin;

class DefaultRequireTest extends TestCase
{

    /**
     * Tests default require provider.
     *
     * @param $provided
     * @param $expected
     * @dataProvider provideTestData
     */
    public function testDefaultRequire($provided, $expected)
    {
        $versionParser = new VersionParser();
        self::assertEquals($expected, Plugin::getDefaultRequire($versionParser->parseConstraints($provided)));
    }

    /**
     * Test data.
     */
    function provideTestData()
    {
        yield 'exact-below' => ['8.2.0', []];
        yield 'exact-above' => ['8.6.0', ['symfony/symfony' => '>3.4']];
        yield 'exact-min' => ['8.5.0', ['symfony/symfony' => '>3.4']];
        yield 'range-below' => ['~8.4.0', []];
        yield 'range-overlapping' => ['>8.4.0 <8.6.0', []];
        yield 'range-below-above' => ['~8.2.0|~8.6.0', []];
        yield 'range-above' => ['~8.6.0', ['symfony/symfony' => '>3.4']];
        yield 'range-min' => ['^8.5', ['symfony/symfony' => '>3.4']];
    }

}
