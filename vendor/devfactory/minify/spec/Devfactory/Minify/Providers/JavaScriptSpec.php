<?php namespace spec\Devfactory\Minify\Providers;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Prophet;
use org\bovigo\vfs\vfsStream;
use Illuminate\Filesystem\Filesystem;



class JavaScriptSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Devfactory\Minify\Providers\JavaScript');
    }

    function it_adds_one_file()
    {
        vfsStream::setup('js',null, array(
            '1.js' => 'a',
        ));

        $this->add(VfsStream::url('js'));
        $this->shouldHaveCount(1);
    }

    function it_adds_multiple_files()
    {
        vfsStream::setup('root',null, array(
            '1.js' => 'a',
            '2.js' => 'b',
        ));

        $this->add(array(
            VfsStream::url('root/1.js'),
            VfsStream::url('root/2.js')
        ));

        $this->shouldHaveCount(2);
    }

    function it_adds_custom_attributes()
    {
        $this->tag('file', array('foobar' => 'baz', 'defer' => true))
            ->shouldReturn('<script src="file" foobar="baz" defer></script>' . PHP_EOL);
    }

    function it_adds_without_custom_attributes()
    {
      $this->tag('file', array())
            ->shouldReturn('<script src="file"></script>' . PHP_EOL);
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
        vfsStream::setup('js',0555, array());

        $this->shouldThrow('Devfactory\Minify\Exceptions\DirNotWritableException')
            ->duringMake(vfsStream::url('js'));
    }

    function it_minifies_multiple_files()
    {
        vfsStream::setup('root',null, array(
            'output' => array(),
            '1.js' => 'a',
            '2.js' => 'b',
        ));

        $this->add(vfsStream::url('root/1.js'));
        $this->add(vfsStream::url('root/2.js'));

        $this->make(vfsStream::url('root/output'));

        $this->getAppended()->shouldBe("a\nb\n");

        $output = md5('vfs://root/1.js-vfs://root/2.js');
        $filemtime = filemtime(vfsStream::url('root/1.js')) + filemtime(vfsStream::url('root/2.js'));
        $extension = '.js';

        $this->getFilename()->shouldBe($output . $filemtime . $extension);
    }
}
