<?php

namespace Lace\Ainstruct\Tests\Unit\Actions\Template;

use Lace\Ainstruct\Actions\Template\CloneTemplateAction;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Repositories\InstructionFileRepository;
use Lace\Ainstruct\Support\Filesystem;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Tests\TestCase;
use Lace\Ainstruct\Values\Template;
use PHPUnit\Framework\Attributes\Test;

final class CloneTemplateActionTest extends TestCase
{
    #[Test]
    public function clone_does_not_inherit_ainstruct_source(): void
    {
        $tmp = sys_get_temp_dir().'/ainstruct-clone-'.bin2hex(random_bytes(4));
        $srcDir = $tmp.'/src';
        $dstDir = $tmp.'/dst';

        $fs = new Filesystem;
        $fs->makeDirectory($srcDir);
        $fs->writeFile($srcDir.'/ai-instructions.md', "# Konstitusi\n");
        $fs->writeFile($srcDir.'/ainstruct.source', "source=/remote/repo\nref=v1\n");

        $files = new InstructionFileRepository(new Paths($tmp, $tmp), $fs);

        $templates = $this->createMock(TemplateRepositoryContract::class);
        $templates->method('findOrFail')->with('src')->willReturn(
            new Template('src', $srcDir, TemplateOrigin::CUSTOM)
        );
        $templates->method('find')->with('dst')->willReturn(null);
        $templates->method('consumerPathFor')->with('dst')->willReturn($dstDir);

        $action = new CloneTemplateAction($templates, $files);
        $result = $action->handle(['name' => 'dst', 'source' => 'src', 'force' => false]);

        $this->assertSame(TemplateOrigin::CUSTOM, $result->origin);
        $this->assertFileExists($dstDir.'/ai-instructions.md');
        $this->assertFileDoesNotExist($dstDir.'/ainstruct.source');

        $this->removeDir($tmp);
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
}
