<?php

namespace Lace\Ainstruct\Tests;

use Illuminate\Container\Container;
use Lace\Ainstruct\Application;
use Lace\Ainstruct\Bootstrap\AppServiceProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var string */
    private $originalDir;

    /** @var list<string> */
    private array $tempDirs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDir = getcwd() ?: '.';
        $this->tempDirs = [];
    }

    protected function tearDown(): void
    {
        chdir($this->originalDir);
        putenv('AINSTRUCT_HOME');

        foreach ($this->tempDirs as $dir) {
            $this->removeDirectory($dir);
        }

        parent::tearDown();
    }

    protected function makeApplication(): Application
    {
        $container = new Container;
        (new AppServiceProvider($container))->register();

        return new Application($container);
    }

    /**
     * Jalankan application dengan output tertangkap.
     *
     * @return array{int, string} [exit code, output]
     */
    protected function runCapture(Application $app, array $args): array
    {
        ob_start();

        try {
            $exit = $app->run($args);
        } finally {
            $output = (string) ob_get_clean();
        }

        return [$exit, $output];
    }

    protected function tempDir(): string
    {
        $dir = sys_get_temp_dir().'/ainstruct-test-'.bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);
        $this->tempDirs[] = $dir;

        return $dir;
    }

    protected function withHome(string $home): void
    {
        putenv('AINSTRUCT_HOME='.$home);
    }

    protected function fixture(string $relative): string
    {
        return __DIR__.'/Fixtures/'.$relative;
    }

    protected function stripAnsi(string $text): string
    {
        return (string) preg_replace('/\033\[[0-9;]*m/', '', $text);
    }

    /**
     * Salin fixture template minimal sebagai template custom konsumen.
     */
    protected function installMinimalTemplate(string $home, string $name = 'minimal'): string
    {
        $source = $this->fixture('templates/minimal');
        $target = $home.'/templates/'.$name;

        mkdir($target, 0777, true);
        mkdir($target.'/ai-instructions', 0777, true);

        copy($source.'/ai-instructions.md', $target.'/ai-instructions.md');
        copy($source.'/ai-instructions/README.md', $target.'/ai-instructions/README.md');
        copy($source.'/ai-instructions/01-intro.md', $target.'/ai-instructions/01-intro.md');
        copy($source.'/ainstruct-detect.txt', $target.'/ainstruct-detect.txt');

        return $target;
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}
