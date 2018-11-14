<?php

namespace Chumper\Zipper\Repositories;

use Exception;
use Mockery;
use ZipArchive;

/**
 * Created by JetBrains PhpStorm.
 * User: Nils
 * Date: 28.08.13
 * Time: 20:57
 * To change this template use File | Settings | File Templates.
 */
class ZipRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZipRepository
     */
    public $zip;

    /**
     * @var \Mockery\Mock
     */
    public $mock;

    public function setUp()
    {
        $this->mock = Mockery::mock(new ZipArchive());
        $this->zip = new ZipRepository('foo', true, $this->mock);
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testMake()
    {
        $zip = new ZipRepository('foo.zip', true);
        $this->assertFalse($zip->fileExists('foo'));
    }

    public function testOpenNonExistentZipThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error: Failed to open idonotexist.zip! Error: ZipArchive::ER_');
        new ZipRepository('idonotexist.zip', false);
    }

    public function testOpenNonZipThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Error: Failed to open (.*)ZipRepositoryTest.php! Error: ZipArchive::ER_NOZIP - Not a zip archive./');
        new ZipRepository(__DIR__.DIRECTORY_SEPARATOR.'ZipRepositoryTest.php', false);
    }

    public function testAddFile()
    {
        $this->mock->shouldReceive('addFile')->once()->with('bar', 'bar');
        $this->mock->shouldReceive('addFile')->once()->with('bar', 'foo/bar');
        $this->mock->shouldReceive('addFile')->once()->with('foo/bar', 'bar');

        $this->zip->addFile('bar', 'bar');
        $this->zip->addFile('bar', 'foo/bar');
        $this->zip->addFile('foo/bar', 'bar');
    }

    public function testRemoveFile()
    {
        $this->mock->shouldReceive('deleteName')->once()->with('bar');
        $this->mock->shouldReceive('deleteName')->once()->with('foo/bar');

        $this->zip->removeFile('bar');
        $this->zip->removeFile('foo/bar');
    }

    public function testGetFileContent()
    {
        $this->mock->shouldReceive('getFromName')->once()
            ->with('bar')->andReturn('foo');
        $this->mock->shouldReceive('getFromName')->once()
            ->with('foo/bar')->andReturn('baz');

        $this->assertSame('foo', $this->zip->getFileContent('bar'));
        $this->assertSame('baz', $this->zip->getFileContent('foo/bar'));
    }

    public function testGetFileStream()
    {
        $this->mock->shouldReceive('getStream')->once()
            ->with('bar')->andReturn('foo');
        $this->mock->shouldReceive('getStream')->once()
            ->with('foo/bar')->andReturn('baz');

        $this->assertSame('foo', $this->zip->getFileStream('bar'));
        $this->assertSame('baz', $this->zip->getFileStream('foo/bar'));
    }

    public function testFileExists()
    {
        $this->mock->shouldReceive('locateName')->once()
            ->with('bar')->andReturn(true);
        $this->mock->shouldReceive('locateName')->once()
            ->with('foo/bar')->andReturn(false);

        $this->assertTrue($this->zip->fileExists('bar'));
        $this->assertFalse($this->zip->fileExists('foo/bar'));
    }

    public function testClose()
    {
        $this->zip->close();
    }
}
