<?php
namespace Tests\Unit;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test \Helper::setEnvFileVar().
 *
 * The real .env file is never touched: the application is pointed to a temporary
 * directory with its own .env file for the time of each test.
 */
class SetEnvFileVarTest extends TestCase
{
    const KEY = 'SETENVFILEVAR_TEST_KEY';

    protected $tmp_dir;
    protected $orig_env_path;
    protected $orig_env_file;
    protected $real_env_file_path;
    protected $real_env_hash;

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Remember the real .env file to make sure it stays untouched.
        $this->real_env_file_path = $this->app->environmentFilePath();
        $this->real_env_hash = is_file($this->real_env_file_path) ? md5_file($this->real_env_file_path) : null;
        $this->orig_env_path = $this->app->environmentPath();
        $this->orig_env_file = $this->app->environmentFile();

        $this->tmp_dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fs-env-test-'.uniqid('', true);
        mkdir($this->tmp_dir, 0700, true);

        // Make \Helper::setEnvFileVar() work with the temporary .env file.
        $this->app->useEnvironmentPath($this->tmp_dir);
        $this->app->loadEnvironmentFrom('.env');

        // Safety guard: never go on if the real .env could be written.
        $this->assertNotEquals($this->real_env_file_path, $this->envPath());
        $this->assertSame($this->tmp_dir, dirname($this->envPath()));
    }

    protected function tearDown(): void
    {
        if ($this->tmp_dir) {
            @unlink($this->tmp_dir.DIRECTORY_SEPARATOR.'.env');
            @rmdir($this->tmp_dir);
        }

        // Restore the original environment file location.
        $this->app->useEnvironmentPath($this->orig_env_path);
        $this->app->loadEnvironmentFrom($this->orig_env_file);

        // The real .env file must not be changed.
        $this->assertSame(
            $this->real_env_hash,
            is_file($this->real_env_file_path) ? md5_file($this->real_env_file_path) : null,
            'The real .env file has been modified'
        );

        parent::tearDown();
    }

    protected function envPath()
    {
        return $this->app->environmentFilePath();
    }

    protected function writeEnv($contents)
    {
        file_put_contents($this->envPath(), $contents);
    }

    protected function readEnv()
    {
        return file_get_contents($this->envPath());
    }

    /**
     * Parse .env contents into [key => [raw value, raw value, ...]].
     * Empty lines are ignored. All values of a key are returned to detect duplicates.
     */
    protected function parseEnv($contents)
    {
        $result = [];
        foreach (preg_split("/\r\n|\n/", $contents) as $line) {
            if ($line === '') {
                continue;
            }
            if (preg_match('/^([A-Za-z0-9_]+)=(.*)$/', $line, $m)) {
                $result[$m[1]][] = $m[2];
            } else {
                $this->fail('Unexpected line in .env: '.$line);
            }
        }

        return $result;
    }

    protected function assertEnvVar($expected_raw_value, $key = self::KEY)
    {
        $env = $this->parseEnv($this->readEnv());
        $this->assertArrayHasKey($key, $env);
        $this->assertSame([$expected_raw_value], $env[$key], 'Key '.$key.' must be present exactly once');
    }

    /**
     * Values as they are expected to be written into .env: [value passed, raw value expected in file].
     */
    public static function valuesProvider()
    {
        return [
            'int zero'     => [0, '0'],
            'string'       => ['abc', 'abc'],
            'empty'        => ['', ''],
            'space'        => ['a b', '"a b"'],
            'quote'        => ['a"b', '"a\\"b"'],
            'dollar digit' => ['pa$1ss', '"pa$1ss"'],
            'nested var'   => ['pa${x}', '"pa\\${x}"'],
        ];
    }

    /**
     * A new var is added once, repeated calls do not add duplicates.
     */
    #[DataProvider('valuesProvider')]
    public function testAddVar($value, $expected): void
    {
        $this->writeEnv("A=1\n");

        for ($i = 0; $i < 3; $i++) {
            \Helper::setEnvFileVar(self::KEY, $value);
        }

        $this->assertSame(['A' => ['1'], self::KEY => [$expected]], $this->parseEnv($this->readEnv()));
    }

    /**
     * An existing var is replaced, including "0" and empty values.
     */
    #[DataProvider('valuesProvider')]
    public function testReplaceVar($value, $expected): void
    {
        foreach (['1', '0', ''] as $old_value) {
            $this->writeEnv("A=1\n".self::KEY.'='.$old_value."\nB=2\n");

            \Helper::setEnvFileVar(self::KEY, $value);

            $this->assertSame(
                ['A' => ['1'], self::KEY => [$expected], 'B' => ['2']],
                $this->parseEnv($this->readEnv()),
                'Old value: "'.$old_value.'"'
            );
        }
    }

    /**
     * A key must not be replaced inside another key ending with the same text.
     */
    public function testKeyIsNotReplacedInsideOtherKey(): void
    {
        foreach (['0', ''] as $old_value) {
            $this->writeEnv('OTHER_'.self::KEY.'='.$old_value."\n".self::KEY.'='.$old_value."\n");

            \Helper::setEnvFileVar(self::KEY, 'new');

            $this->assertSame(
                ['OTHER_'.self::KEY => [$old_value], self::KEY => ['new']],
                $this->parseEnv($this->readEnv()),
                'Old value: "'.$old_value.'"'
            );
        }
    }
}
