<?php
namespace Ecfectus\Test\Config;

use Ecfectus\Config\FileLoader;

class FileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function sampleFiles()
    {
        return __DIR__ . '/testfiles';
    }

    public function testFileLoadCanLoadFiles()
    {
        $dir = $this->sampleFiles();

        $loader = new FileLoader($dir);

        $loaded = $loader->load(null, 'app');

        $this->assertSame(include $dir . '/app.php', $loaded);
    }


    public function testANoneExistentFileLoad()
    {
        $dir = $this->sampleFiles();

        $loader = new FileLoader($dir);

        $loaded = $loader->load(null, 'database');

        $this->assertEquals(null, $loaded);
    }

    public function testAFileLoadInMergedEnviroment()
    {
        $dir = $this->sampleFiles();

        $loader = new FileLoader($dir);

        $loaded = $loader->load('staging', 'app');

        $merged = array_replace_recursive(
                include $dir . '/app.php',
                include $dir . '/staging/app.php');

        $this->assertSame($merged, $loaded);
    }
}
