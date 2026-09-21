<?php

namespace Lace\Ainstruct\Tests\Unit\Web;

use Lace\Ainstruct\Exceptions\ValidationException;
use Lace\Ainstruct\Services\Template\TemplateFileService;
use Lace\Ainstruct\Support\Filesystem;
use Lace\Ainstruct\Tests\TestCase;

final class TemplateFileServiceTest extends TestCase
{
    private TemplateFileService $service;

    private string $templateDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TemplateFileService(new Filesystem);
        $this->templateDir = $this->tempDir().'/tpl';
        mkdir($this->templateDir.'/ai-instructions', 0777, true);
        file_put_contents($this->templateDir.'/ai-instructions.md', "# Konstitusi\n");
        file_put_contents($this->templateDir.'/ai-instructions/01-intro.md', "# Intro\n");
        file_put_contents($this->templateDir.'/ainstruct-detect.txt', "5|file|artisan|CLI\n");
    }

    public function test_tree_lists_files_and_directories_dirs_first(): void
    {
        $entries = $this->service->tree($this->templateDir);

        $this->assertSame('dir', $entries[0]['type']);
        $this->assertSame('ai-instructions', $entries[0]['name']);
        $this->assertTrue(in_array('ai-instructions.md', array_column($entries, 'name'), true));
        $this->assertTrue(in_array('ainstruct-detect.txt', array_column($entries, 'name'), true));
    }

    public function test_tree_descends_into_subdirectory(): void
    {
        $entries = $this->service->tree($this->templateDir, 'ai-instructions');

        $this->assertSame('01-intro.md', $entries[0]['name']);
        $this->assertSame('file', $entries[0]['type']);
        $this->assertSame('ai-instructions/01-intro.md', $entries[0]['path']);
    }

    public function test_tree_skips_symlink_entries(): void
    {
        if (! @symlink('/etc/hostname', $this->templateDir.'/luar')) {
            $this->markTestSkipped('symlink tidak tersedia di lingkungan ini');
        }

        $entries = $this->service->tree($this->templateDir);
        $names = array_column($entries, 'name');

        $this->assertNotContains('luar', $names);
    }

    public function test_tree_rejects_path_traversal(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->tree($this->templateDir, '../..');
    }

    public function test_read_returns_text_content(): void
    {
        $file = $this->service->read($this->templateDir, 'ai-instructions.md');

        $this->assertSame('ai-instructions.md', $file['path']);
        $this->assertStringContainsString('Konstitusi', $file['content']);
        $this->assertGreaterThan(0, $file['size']);
    }

    public function test_read_rejects_traversal(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->read($this->templateDir, '../kompromi.txt');
    }

    public function test_read_rejects_binary_content(): void
    {
        file_put_contents($this->templateDir.'/biner.bin', "teks\x00teks");

        $this->expectException(ValidationException::class);

        $this->service->read($this->templateDir, 'biner.bin');
    }

    public function test_read_rejects_file_over_two_mb(): void
    {
        file_put_contents($this->templateDir.'/besar.txt', str_repeat('a', 2 * 1024 * 1024 + 1));

        $this->expectException(ValidationException::class);

        $this->service->read($this->templateDir, 'besar.txt');
    }

    public function test_read_rejects_symlink_pointing_outside(): void
    {
        $outside = $this->tempDir().'/rahasia.txt';
        file_put_contents($outside, 'rahasia');

        if (! @symlink($outside, $this->templateDir.'/bocor.txt')) {
            $this->markTestSkipped('symlink tidak tersedia di lingkungan ini');
        }

        $this->expectException(ValidationException::class);

        $this->service->read($this->templateDir, 'bocor.txt');
    }

    public function test_write_creates_file_inside_template(): void
    {
        $this->service->write($this->templateDir, 'baru/note.md', 'konten baru');

        $this->assertFileExists($this->templateDir.'/baru/note.md');
        $this->assertSame('konten baru', (string) file_get_contents($this->templateDir.'/baru/note.md'));
    }

    public function test_write_rejects_traversal(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->write($this->templateDir, '../keluar.txt', 'isi');
    }

    public function test_write_rejects_binary_content(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->write($this->templateDir, 'file.txt', "teks\x00biner");
    }

    public function test_write_rejects_symlink_target(): void
    {
        $outside = $this->tempDir().'/target.txt';
        file_put_contents($outside, 'asli');

        if (! @symlink($outside, $this->templateDir.'/pintu.txt')) {
            $this->markTestSkipped('symlink tidak tersedia di lingkungan ini');
        }

        $this->expectException(ValidationException::class);

        $this->service->write($this->templateDir, 'pintu.txt', 'timpa');
    }
}
