<?php namespace spec\Devfactory\Minify\Providers;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Prophet;
use org\bovigo\vfs\vfsStream;
use Illuminate\Filesystem\Filesystem;

class StyleSheetSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Devfactory\Minify\Providers\StyleSheet');
    }

    function it_adds_one_file()
    {
        vfsStream::setup('css',null, array(
            '1.css' => 'a',
        ));

        $this->add(VfsStream::url('css'));
        $this->shouldHaveCount(1);
    }

    function it_adds_multiple_files()
    {
        vfsStream::setup('root',null, array(
            '1.css' => 'a',
            '2.css' => 'b',
        ));

        $this->add(array(
            VfsStream::url('root/1.css'),
            VfsStream::url('root/2.css')
        ));

        $this->shouldHaveCount(2);
    }

    function it_adds_custom_attributes()
    {
        $this->tag('file', array('foobar' => 'baz', 'defer' => true))
            ->shouldReturn('<link href="file" rel="stylesheet" foobar="baz" defer>' . PHP_EOL);
    }

    function it_adds_without_custom_attributes()
    {
        $this->tag('file')
            ->shouldReturn('<link href="file" rel="stylesheet">' . PHP_EOL);
    }

    function it_throws_exception_when_file_not_exists()
    {
        $this->shouldThrow('Devfactory\Minify\Exceptions\FileNotExistException')
            ->duringAdd('foobar');
    }

    function it_should_throw_exception_when_buildpath_not_exist()
    {
      $prophet = new Prophet;
      $file = $prophet->prophesize('Illuminate\Filesystem\Filesystem');
      $file->makeDirectory('dir_bar', 0775, true)->willReturn(false);

      $this->beConstructedWith(null, null, $file);
      $this->shouldThrow('Devfactory\Minify\Exceptions\DirNotExistException')
            ->duringMake('dir_bar');
    }

    function it_should_throw_exception_when_buildpath_not_writable()
    {
        vfsStream::setup('css',0555, array());

        $this->shouldThrow('Devfactory\Minify\Exceptions\DirNotWritableException')
            ->duringMake(vfsStream::url('css'));
    }

    function it_minifies_multiple_files()
    {
        vfsStream::setup('root',null, array(
            'output' => array(),
            '1.css' => 'a',
            '2.css' => 'b',
        ));

        $this->add(vfsStream::url('root/1.css'));
        $this->add(vfsStream::url('root/2.css'));

        $this->make(vfsStream::url('root/output'));

        $this->getAppended()->shouldBe("a\nb\n");

        $output = md5('vfs://root/1.css-vfs://root/2.css');
        $filemtime = filemtime(vfsStream::url('root/1.css')) + filemtime(vfsStream::url('root/2.css'));
        $extension = '.css';

        $this->getFilename()->shouldBe($output . $filemtime . $extension);
    }
}
