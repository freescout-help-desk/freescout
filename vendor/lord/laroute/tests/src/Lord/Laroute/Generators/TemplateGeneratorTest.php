<?php

namespace Lord\Laroute\Generators;

use Mockery;

class TemplateGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $compiler;

    protected $filesystem;

    protected $generator;

    public function setUp()
    {
        parent::setUp();

        $this->compiler   = $this->mock('Lord\Laroute\Compilers\CompilerInterface');
        $this->filesystem = $this->mock('Illuminate\Filesystem\Filesystem');

        $this->generator = new TemplateGenerator($this->compiler, $this->filesystem);
    }

    public function testItIsOfTheCorrectInterface()
    {
        $this->assertInstanceOf(
            'Lord\Laroute\Generators\GeneratorInterface',
            $this->generator
        );
    }

    public function testItWillCompileAndSaveATemplate()
    {
        $template     = "Template";
        $templatePath = '/templatePath';
        $templateData = ['foo', 'bar'];
        $filePath     = '/filePath';

        $this->filesystem
            ->shouldReceive('get')
            ->once()
            ->with($templatePath)
            ->andReturn($template);

        $this->filesystem
            ->shouldReceive('isDirectory')
            ->once()
            ->andReturn(true);

        $this->compiler
            ->shouldReceive('compile')
            ->once()
            ->with($template, $templateData)
            ->andReturn($template);

        $this->filesystem
            ->shouldReceive('put')
            ->once()
            ->with($filePath, $template);

        $actual = $this->generator->compile($templatePath, $templateData, $filePath);
        $this->assertSame($actual, $filePath);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    protected function mock($class, $app = [])
    {
        $mock = Mockery::mock($class, $app);

        return $mock;
    }
}
