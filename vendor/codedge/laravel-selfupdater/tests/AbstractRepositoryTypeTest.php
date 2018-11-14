<?php

namespace Codedge\Updater\Tests;

class AbstractRepositoryTypeTest extends TestCase
{
    /**
     * @dataProvider pathProvider
     * @param $storagePath
     * @param $releaseName
     */
    public function testCreateReleaseFolder($storagePath, $releaseName)
    {
        $dir = $storagePath.'/'.$releaseName;
        $this->assertTrue(mkdir($dir), 'Release folder ['.$dir.'] created.');
        $this->assertFileExists($dir, 'Release folder ['.$dir.'] exists.');
        $this->assertTrue(rmdir($dir), 'Release folder ['.$dir.'] deleted.');
        $this->assertFileNotExists($dir, 'Release folder ['.$dir.'] does not exist.');
    }

    public function pathProvider()
    {
        return [
            ['/tmp', '1'],
            ['/tmp', '1.1'],
            ['/tmp', '1.2'],
            ['/tmp', 'v1.2'],
        ];
    }
}