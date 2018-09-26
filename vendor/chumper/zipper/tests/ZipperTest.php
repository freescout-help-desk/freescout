<?php

namespace Chumper\Zipper;

use Exception;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Mockery;
use RuntimeException;

class ZipperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Chumper\Zipper\Zipper
     */
    public $archive;

    /**
     * @var \Mockery\Mock
     */
    public $file;

    protected function setUp()
    {
        $this->file = Mockery::mock(new Filesystem());
        $this->archive = new Zipper($this->file);
        $this->archive->make('foo', new ArrayArchive('foo', true));
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testMake()
    {
        $this->assertSame('Chumper\\Zipper\\ArrayArchive', $this->archive->getArchiveType());
        $this->assertSame('foo', $this->archive->getFilePath());
    }

    public function testMakeThrowsExceptionWhenCouldNotCreateDirectory()
    {
        $path = getcwd().time();

        $this->file->shouldReceive('makeDirectory')
            ->with($path, 0755, true)
            ->andReturn(false);

        $zip = new Zipper($this->file);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create folder');

        $zip->make($path.DIRECTORY_SEPARATOR.'createMe.zip');
    }

    public function testAddAndGet()
    {
        $this->file->shouldReceive('isFile')->with('foo.bar')
            ->times(1)->andReturn(true);
        $this->file->shouldReceive('isFile')->with('foo')
            ->times(1)->andReturn(true);

        $this->archive->add('foo.bar');
        $this->archive->add('foo');

        $this->assertSame('foo', $this->archive->getFileContent('foo'));
        $this->assertSame('foo.bar', $this->archive->getFileContent('foo.bar'));
    }

    public function testAddAndGetWithArray()
    {
        $this->file->shouldReceive('isFile')->with('foo.bar')
            ->times(1)->andReturn(true);
        $this->file->shouldReceive('isFile')->with('foo')
            ->times(1)->andReturn(true);

        /**Array**/
        $this->archive->add([
            'foo.bar',
            'foo',
        ]);

        $this->assertSame('foo', $this->archive->getFileContent('foo'));
        $this->assertSame('foo.bar', $this->archive->getFileContent('foo.bar'));
    }
    
    public function testAddAndGetWithCustomFilenameArray()
    {
        $this->file->shouldReceive('isFile')->with('foo.bar')
            ->times(1)->andReturn(true);
        $this->file->shouldReceive('isFile')->with('foo')
            ->times(1)->andReturn(true);

        /**Array**/
        $this->archive->add([
            'custom.bar' => 'foo.bar',
            'custom' => 'foo',
        ]);

        $this->assertSame('custom', $this->archive->getFileContent('custom'));
        $this->assertSame('custom.bar', $this->archive->getFileContent('custom.bar'));
    }

    public function testAddAndGetWithSubFolder()
    {
        /*
         * Add the local folder /path/to/fooDir as folder fooDir to the repository
         * and make sure the folder structure within the repository is there.
         */
        $this->file->shouldReceive('isFile')->with('/path/to/fooDir')
            ->once()->andReturn(false);

        $this->file->shouldReceive('files')->with('/path/to/fooDir')
            ->once()->andReturn(['fileInFooDir.bar', 'fileInFooDir.foo']);

        $this->file->shouldReceive('directories')->with('/path/to/fooDir')
            ->once()->andReturn(['fooSubdir']);

        $this->file->shouldReceive('files')->with('/path/to/fooDir/fooSubdir')
            ->once()->andReturn(['fileInFooDir.bar']);
        $this->file->shouldReceive('directories')->with('/path/to/fooDir/fooSubdir')
            ->once()->andReturn([]);

        $this->archive->folder('fooDir')
            ->add('/path/to/fooDir');

        $this->assertSame('fooDir/fileInFooDir.bar', $this->archive->getFileContent('fooDir/fileInFooDir.bar'));
        $this->assertSame('fooDir/fileInFooDir.foo', $this->archive->getFileContent('fooDir/fileInFooDir.foo'));
        $this->assertSame('fooDir/fooSubdir/fileInFooDir.bar', $this->archive->getFileContent('fooDir/fooSubdir/fileInFooDir.bar'));
    }

    public function testGetFileContent()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The file "baz" cannot be found');

        $this->archive->getFileContent('baz');
    }

    public function testRemove()
    {
        $this->file->shouldReceive('isFile')->with('foo')
            ->andReturn(true);

        $this->archive->add('foo');

        $this->assertTrue($this->archive->contains('foo'));

        $this->archive->remove('foo');

        $this->assertFalse($this->archive->contains('foo'));

        //----

        $this->file->shouldReceive('isFile')->with('foo')
            ->andReturn(true);
        $this->file->shouldReceive('isFile')->with('fooBar')
            ->andReturn(true);

        $this->archive->add(['foo', 'fooBar']);

        $this->assertTrue($this->archive->contains('foo'));
        $this->assertTrue($this->archive->contains('fooBar'));

        $this->archive->remove(['foo', 'fooBar']);

        $this->assertFalse($this->archive->contains('foo'));
        $this->assertFalse($this->archive->contains('fooBar'));
    }

    public function testExtractWhiteList()
    {
        $this->file
            ->shouldReceive('isFile')
            ->with('foo')
            ->andReturn(true);

        $this->file
            ->shouldReceive('isFile')
            ->with('foo.log')
            ->andReturn(true);

        $this->archive
            ->add('foo')
            ->add('foo.log');

        $this->file
            ->shouldReceive('put')
            ->with(realpath(null).DIRECTORY_SEPARATOR.'foo', 'foo');

        $this->file
            ->shouldReceive('put')
            ->with(realpath(null).DIRECTORY_SEPARATOR.'foo.log', 'foo.log');

        $this->archive
            ->extractTo(getcwd(), ['foo'], Zipper::WHITELIST);
    }

    public function testExtractToThrowsExceptionWhenCouldNotCreateDirectory()
    {
        $path = getcwd().time();

        $this->file
            ->shouldReceive('isFile')
            ->with('foo.log')
            ->andReturn(true);

        $this->file->shouldReceive('makeDirectory')
            ->with($path, 0755, true)
            ->andReturn(false);

        $this->archive->add('foo.log');

        $this->file->shouldNotReceive('put')
            ->with(realpath(null).DIRECTORY_SEPARATOR.'foo.log', 'foo.log');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create folder');

        $this->archive
            ->extractTo($path, ['foo'], Zipper::WHITELIST);
    }

    public function testExtractWhiteListFromSubDirectory()
    {
        $this->file->shouldReceive('isFile')->andReturn(true);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive
            ->folder('foo/bar')
            ->add('baz')
            ->add('baz.log');

        $this->file
            ->shouldReceive('put')
            ->with(realpath(null).DIRECTORY_SEPARATOR.'baz', 'foo/bar/baz');

        $this->file
            ->shouldReceive('put')
            ->with(realpath(null).DIRECTORY_SEPARATOR.'baz.log', 'foo/bar/baz.log');

        $this->archive
            ->extractTo(getcwd(), ['baz'], Zipper::WHITELIST);
    }

    public function testExtractWhiteListWithExactMatching()
    {
        $this->file->shouldReceive('isFile')->andReturn(true);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive
            ->folder('foo/bar')
            ->add('baz')
            ->add('baz.log');

        $this->file
            ->shouldReceive('put')
            ->with(realpath(null).DIRECTORY_SEPARATOR.'baz', 'foo/bar/baz');

        $this->archive
            ->extractTo(getcwd(), ['baz'], Zipper::WHITELIST | Zipper::EXACT_MATCH);
    }

    public function testExtractWhiteListWithExactMatchingFromSubDirectory()
    {
        $this->file->shouldReceive('isFile')->andReturn(true);
        $this->file->shouldReceive('exists')->andReturn(false);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive->folder('foo/bar/subDirectory')
            ->add('bazInSubDirectory')
            ->add('bazInSubDirectory.log');

        $this->archive->folder('foo/bar')
            ->add('baz')
            ->add('baz.log');

        $subDirectoryPath = realpath(null).DIRECTORY_SEPARATOR.'subDirectory';
        $subDirectoryFilePath = $subDirectoryPath.'/bazInSubDirectory';
        $this->file->shouldReceive('put')
            ->with($subDirectoryFilePath, 'foo/bar/subDirectory/bazInSubDirectory');

        $this->archive
            ->extractTo(getcwd(), ['subDirectory/bazInSubDirectory'], Zipper::WHITELIST | Zipper::EXACT_MATCH);

        $this->file->shouldHaveReceived('makeDirectory')->with($subDirectoryPath, 0755, true, true);
    }

    public function testExtractToIgnoresBlackListFile()
    {
        $this->file->shouldReceive('isFile')->with('foo')
            ->andReturn(true);
        $this->file->shouldReceive('isFile')->with('bar')
            ->andReturn(true);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive->add('foo')
            ->add('bar');

        $this->file->shouldReceive('put')->with(realpath(null).DIRECTORY_SEPARATOR.'foo', 'foo');
        $this->file->shouldNotReceive('put')->with(realpath(null).DIRECTORY_SEPARATOR.'bar', 'bar');

        $this->archive->extractTo(getcwd(), ['bar'], Zipper::BLACKLIST);
    }

    public function testExtractBlackListFromSubDirectory()
    {
        $currentDir = getcwd();

        $this->file->shouldReceive('isFile')->andReturn(true);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive->add('rootLevelFile');

        $this->archive->folder('foo/bar/sub')
            ->add('fileInSubSubDir');

        $this->archive->folder('foo/bar')
            ->add('fileInSubDir')
            ->add('fileBlackListedInSubDir');

        $this->file->shouldReceive('put')->with($currentDir.DIRECTORY_SEPARATOR.'fileInSubDir', 'foo/bar/fileInSubDir');
        $this->file->shouldReceive('put')->with($currentDir.DIRECTORY_SEPARATOR.'sub/fileInSubSubDir', 'foo/bar/sub/fileInSubSubDir');

        $this->file->shouldNotReceive('put')->with($currentDir.DIRECTORY_SEPARATOR.'fileBlackListedInSubDir', 'fileBlackListedInSubDir');
        $this->file->shouldNotReceive('put')->with($currentDir.DIRECTORY_SEPARATOR.'rootLevelFile', 'rootLevelFile');

        $this->archive->extractTo($currentDir, ['fileBlackListedInSubDir'], Zipper::BLACKLIST);
    }

    public function testExtractBlackListFromSubDirectoryWithExactMatching()
    {
        $this->file->shouldReceive('isFile')->with('baz')
            ->andReturn(true);
        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->file->shouldReceive('isFile')->with('baz.log')
            ->andReturn(true);

        $this->archive->folder('foo/bar')
            ->add('baz')
            ->add('baz.log');

        $this->file->shouldReceive('put')->with(realpath(null).DIRECTORY_SEPARATOR.'baz.log', 'foo/bar/baz.log');

        $this->archive->extractTo(getcwd(), ['baz'], Zipper::BLACKLIST | Zipper::EXACT_MATCH);
    }

    public function testExtractMatchingRegexFromSubFolder()
    {
        $this->file->shouldReceive('isFile')->with('baz')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('baz.log')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('subFolderFileToIgnore')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('subFolderFileToExtract.log')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('rootLevelMustBeIgnored.log')->andReturn(true);

        $this->file->shouldReceive('makeDirectory')->andReturn(true);

        $this->archive->add('rootLevelMustBeIgnored.log');

        $this->archive->folder('foo/bar/subFolder')
            ->add('subFolderFileToIgnore')
            ->add('subFolderFileToExtract.log');

        $this->archive->folder('foo/bar')
            ->add('baz')
            ->add('baz.log');

        $this->file->shouldReceive('put')->with(realpath(null).DIRECTORY_SEPARATOR.'baz.log', 'foo/bar/baz.log');
        $this->file->shouldReceive('put')->with(realpath(null).DIRECTORY_SEPARATOR.'subFolder/subFolderFileToExtract.log', 'foo/bar/subFolder/subFolderFileToExtract.log');
        $this->file->shouldNotReceive('put')->with(realpath(null).DIRECTORY_SEPARATOR.'rootLevelMustBeIgnored.log', 'rootLevelMustBeIgnored.log');
        $this->file->shouldNotReceive('put')->with(realpath(null).DIRECTORY_SEPARATOR.'baz', 'foo/bar/baz');
        $this->file->shouldNotReceive('put')->with(realpath(null).DIRECTORY_SEPARATOR.'subFolder/subFolderFileToIgnore', 'foo/bar/subFolder/subFolderFileToIgnore');

        $this->archive->extractMatchingRegex(getcwd(), '/\.log$/i');
    }

    public function testExtractMatchingRegexThrowsExceptionWhenRegexIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing pass valid regex parameter');
        $this->archive->extractMatchingRegex(getcwd(), '');
    }

    public function testNavigationFolderAndHome()
    {
        $this->archive->folder('foo/bar');
        $this->assertSame('foo/bar', $this->archive->getCurrentFolderPath());

        //----

        $this->file->shouldReceive('isFile')->with('foo')
            ->andReturn(true);

        $this->archive->add('foo');
        $this->assertSame('foo/bar/foo', $this->archive->getFileContent('foo/bar/foo'));

        //----

        $this->file->shouldReceive('isFile')->with('bar')
            ->andReturn(true);

        $this->archive->home()->add('bar');
        $this->assertSame('bar', $this->archive->getFileContent('bar'));

        //----

        $this->file->shouldReceive('isFile')->with('baz/bar/bing')
            ->andReturn(true);

        $this->archive->folder('test')->add('baz/bar/bing');
        $this->assertSame('test/bing', $this->archive->getFileContent('test/bing'));
    }

    public function testListFiles()
    {
        // testing empty file
        $this->file->shouldReceive('isFile')->with('foo.file')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('bar.file')->andReturn(true);

        $this->assertSame([], $this->archive->listFiles());

        // testing not empty file
        $this->archive->add('foo.file');
        $this->archive->add('bar.file');

        $this->assertSame(['foo.file', 'bar.file'], $this->archive->listFiles());

        // testing with a empty sub dir
        $this->file->shouldReceive('isFile')->with('/path/to/subDirEmpty')->andReturn(false);

        $this->file->shouldReceive('files')->with('/path/to/subDirEmpty')->andReturn([]);
        $this->file->shouldReceive('directories')->with('/path/to/subDirEmpty')->andReturn([]);
        $this->archive->folder('subDirEmpty')->add('/path/to/subDirEmpty');

        $this->assertSame(['foo.file', 'bar.file'], $this->archive->listFiles());

        // testing with a not empty sub dir
        $this->file->shouldReceive('isFile')->with('/path/to/subDir')->andReturn(false);
        $this->file->shouldReceive('isFile')->with('sub.file')->andReturn(true);

        $this->file->shouldReceive('files')->with('/path/to/subDir')->andReturn(['sub.file']);
        $this->file->shouldReceive('directories')->with('/path/to/subDir')->andReturn([]);

        $this->archive->folder('subDir')->add('/path/to/subDir');

        $this->assertSame(['foo.file', 'bar.file', 'subDir/sub.file'], $this->archive->listFiles());
    }

    public function testListFilesWithRegexFilter()
    {
        // add 2 files to root level in zip
        $this->file->shouldReceive('isFile')->with('foo.file')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('bar.log')->andReturn(true);

        $this->archive
            ->add('foo.file')
            ->add('bar.log');

        // add sub directory with 2 files inside
        $this->file->shouldReceive('isFile')->with('/path/to/subDir')->andReturn(false);
        $this->file->shouldReceive('isFile')->with('sub.file')->andReturn(true);
        $this->file->shouldReceive('isFile')->with('anotherSub.log')->andReturn(true);

        $this->file->shouldReceive('files')->with('/path/to/subDir')->andReturn(['sub.file', 'anotherSub.log']);
        $this->file->shouldReceive('directories')->with('/path/to/subDir')->andReturn([]);

        $this->archive->folder('subDir')->add('/path/to/subDir');

        $this->assertSame(
            ['foo.file', 'subDir/sub.file'],
            $this->archive->listFiles('/\.file$/i') // filter out files ending with ".file" pattern
        );
    }

    public function testListFilesThrowsExceptionWithInvalidRegexFilter()
    {
        $this->file->shouldReceive('isFile')->with('foo.file')->andReturn(true);
        $this->archive->add('foo.file');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('regular expression match on \'foo.file\' failed with error. Please check if pattern is valid regular expression.');

        $invalidPattern = 'asdasd';
        $this->archive->listFiles($invalidPattern);
    }
}
