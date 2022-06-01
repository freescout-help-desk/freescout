<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConfigTest extends TestCase
{
    protected $original_app_key;
    protected $original_app_key_file;
    protected $app_key_file = __DIR__ . "/.keyfile";
    protected $app_key_file_content;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

    protected function setUp()
    {
      $this->original_app_key = getenv("APP_KEY");
      $this->original_app_key_file = getenv("APP_KEY_FILE");
      # The assumption is, that file_get_contents is working
      # So we can use this to avoid double values
      $this->app_key_file_content = trim(file_get_contents($this->app_key_file));
      parent::setUp();
    }

    public function tearDown() {
      # This is to ensure tests don't influence each other
      putenv("APP_KEY=" . $this->original_app_key);
      putenv("APP_KEY_FILE=" . $this->original_app_key_file);
    }

    protected function assertKey($value) {
      $config = require __DIR__.'/../../config/app.php';
      $this->assertEquals($config["key"], $value);
    }

    protected function set_key($value, $suffix = null)
    {
      $key = "APP_KEY";
      if (!empty($suffix)) {
        $key = sprintf("APP_KEY_%s", $suffix);
      }
      $sep = "=";
      if (empty($value)) {
        # So we can unset this
        $sep = "";
      }
      putenv(sprintf("%s%s%s", $key, $sep, $value));
    }

    public function test_app_key_from_environment()
    {
      $orig_env = getenv("APP_KEY");
      $key = "configkeyfromenvironment";
      $this->set_key($key);
      $this->assertKey($key);
    }

    public function test_app_key_from_file()
    {
      $this->set_key(null);
      $this->set_key($this->app_key_file, "FILE");
      $this->assertKey($this->app_key_file_content);
    }

    public function test_environmen_takes_precedence()
    {
      $env_key = "configkeyfromenvironment";
      $this->set_key($env_key);
      $this->set_key($this->app_key_file, "FILE");
      $this->assertKey($env_key);
    }
}
