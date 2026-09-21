<?php

namespace Lace\Ainstruct\Tests\Unit\Services\Template;

use Lace\Ainstruct\Repositories\InstructionFileRepository;
use Lace\Ainstruct\Services\Template\SourceImporter;
use Lace\Ainstruct\Support\Filesystem;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class SourceImporterTest extends TestCase
{
    private SourceImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importer = new SourceImporter(
            new InstructionFileRepository(new Paths(sys_get_temp_dir(), sys_get_temp_dir()), new Filesystem)
        );
    }

    #[Test]
    public function it_expands_tilde_to_home(): void
    {
        $home = sys_get_temp_dir().'/ainstruct-home-'.bin2hex(random_bytes(4));
        mkdir($home);
        $previous = getenv('HOME');
        putenv('HOME='.$home);

        try {
            $this->assertSame($home, $this->importer->expandSource('~'));
            $this->assertSame($home.'/repo', $this->importer->expandSource('~/repo'));
            $this->assertSame('/abs/repo', $this->importer->expandSource('/abs/repo'));
            $this->assertSame('https://github.com/user/repo', $this->importer->expandSource('https://github.com/user/repo'));
        } finally {
            if ($previous === false) {
                putenv('HOME');
            } else {
                putenv('HOME='.$previous);
            }

            @rmdir($home);
        }
    }

    #[Test]
    public function it_imports_tilde_source_from_home(): void
    {
        $home = sys_get_temp_dir().'/ainstruct-home-'.bin2hex(random_bytes(4));
        $repo = $home.'/repo-tilde';
        $target = sys_get_temp_dir().'/ainstruct-tilde-target-'.bin2hex(random_bytes(4));

        $this->createGitTemplateRepo($repo);
        $previous = getenv('HOME');
        putenv('HOME='.$home);

        try {
            $this->importer->import('~/repo-tilde', $target, null);

            $this->assertFileExists($target.'/ai-instructions.md');
            $this->assertFileDoesNotExist($target.'/.git');
        } finally {
            if ($previous === false) {
                putenv('HOME');
            } else {
                putenv('HOME='.$previous);
            }

            $this->removeDir($home);
            $this->removeDir($target);
        }
    }

    private function createGitTemplateRepo(string $dir): void
    {
        mkdir($dir, 0777, true);
        mkdir($dir.'/ai-instructions');
        file_put_contents($dir.'/ai-instructions.md', "# AI INSTRUCTION SYSTEM\n");
        file_put_contents($dir.'/ai-instructions/01-governance.md', "# modul\n");

        $cwd = getcwd();
        chdir($dir);

        try {
            exec('git init -q');
            exec('git config user.name test');
            exec('git config user.email test@example.test');
            exec('git add -A');
            exec('git commit -qm init');
        } finally {
            chdir($cwd);
        }
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($dir);
    }

    public function test_is_git_source_accepts_urls_and_local_paths(): void
    {
        foreach ([
            'https://github.com/user/repo',
            'https://github.com/user/repo.git',
            'ssh://git@github.com/user/repo.git',
            'git@github.com:user/repo.git',
            '/home/user/templates/repo',
            './repo',
            '../repo',
            '~/repo',
            'github.com/user/repo.git',
        ] as $source) {
            $this->assertTrue($this->importer->isGitSource($source), "harus git source: {$source}");
        }
    }

    public function test_is_git_source_rejects_template_names(): void
    {
        foreach ([
            'laravel',
            'vanilla-php',
            'my-template',
            '',
        ] as $source) {
            $this->assertFalse($this->importer->isGitSource($source), "bukan git source: {$source}");
        }
    }
}
