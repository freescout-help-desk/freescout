<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    public function testLatestVersionFromPlainVersionNumber()
    {
        $this->assertSame('1.2.3', \App\Module::parseLatestVersion("1.2.3\n"));
    }

    public function testLatestVersionFromModuleJson()
    {
        $module_json = json_encode([
            'name'               => 'Example Module',
            'alias'              => 'example',
            'requiredAppVersion' => '1.8.200',
            'version'            => '2.10.4',
            'latestVersionUrl'   => 'https://raw.githubusercontent.com/example/example/main/module.json',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->assertSame('2.10.4', \App\Module::parseLatestVersion($module_json));
    }

    public function testLatestVersionFromEmptyResponse()
    {
        $this->assertSame('', \App\Module::parseLatestVersion(" \n"));
    }
}
