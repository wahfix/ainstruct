<?php

namespace Lace\Ainstruct\Tests\Feature\Console;

use Lace\Ainstruct\Tests\TestCase;

final class TemplateCommandTest extends TestCase
{
    public function test_template_list_shows_builtin_and_custom(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home);

        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'list']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Template AI Instructions', $output);
        $this->assertStringContainsString('Built-in (TERPROTEKSI):', $output);
        $this->assertStringContainsString('Custom (milik konsumen', $output);
        $this->assertStringContainsString('•', $output);
        $this->assertStringContainsString('minimal', $this->stripAnsi($output));
    }

    public function test_template_list_when_no_custom(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        // Tanpa custom template; belum ada template built-in juga (fixture home kosong).
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'list']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Belum ada template custom', $output);
    }

    public function test_template_path_shows_custom_path(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home, 'namaku');

        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'path', 'namaku']);

        $this->assertSame(0, $exit);
        $this->assertSame($home.'/templates/namaku'.PHP_EOL, $output);
    }

    public function test_template_create_scaffolds_and_distributes(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'create', 'xyz']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString("Template 'xyz' berhasil dibuat", $output);

        $this->assertFileExists($home.'/templates/xyz/ai-instructions.md');
        $this->assertFileExists($home.'/templates/xyz/ai-instructions/README.md');
        $this->assertFileExists($home.'/templates/xyz/ainstruct-detect.txt');

        $detect = (string) file_get_contents($home.'/templates/xyz/ainstruct-detect.txt');
        $this->assertStringContainsString('SINYAL', strtoupper($detect));
    }

    public function test_template_create_conflicts_with_builtin_without_force(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        // 'laravel' adalah built-in dari instalasi ainstruct — konflik nama tanpa --force.
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'create', 'laravel']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString("Nama 'laravel' sudah dipakai", $output);
    }

    public function test_template_create_force_shadows_builtin_name(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        // Custom 'laravel' dibuat dengan --force → shadow terhadap built-in.
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'create', 'laravel', '--force']);

        $this->assertSame(0, $exit);
        $this->assertFileExists($home.'/templates/laravel/ai-instructions.md');
    }

    public function test_template_clone_copies_source(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home);

        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'clone', 'salinan', 'minimal']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString("Template 'salinan' dibuat dari minimal", $output);
        $this->assertFileExists($home.'/templates/salinan/ai-instructions.md');
        $this->assertFileExists($home.'/templates/salinan/ai-instructions/01-intro.md');
        $this->assertFileExists($home.'/templates/salinan/ainstruct-detect.txt');
    }

    public function test_template_clone_unknown_source_fails(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'clone', 'salinan', 'nihil']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('nihil', $output);
    }

    public function test_template_update_from_builtin_overwrites_custom(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $this->installMinimalTemplate($home, 'minimal');

        // Sumber dari built-in 'laravel' (packageRoot/templates/laravel).
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'update', 'minimal', '--from', 'laravel', '--force']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString("Template 'minimal' diperbarui", $output);
        $this->assertFileExists($home.'/templates/minimal/ai-instructions/01-governance.md');
        $this->assertStringContainsString(
            'AI INSTRUCTION SYSTEM',
            strtoupper((string) file_get_contents($home.'/templates/minimal/ai-instructions.md'))
        );
    }

    public function test_template_update_protected_builtin_fails(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        // Tidak ada custom 'laravel' → built-in terproteksi.
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'update', 'laravel', '--force']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('built-in TERPROTEKSI', $output);
    }

    public function test_template_delete_removes_custom(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home, 'namaku');

        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'delete', 'namaku', '--force']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString("Template 'namaku' dihapus", $output);
        $this->assertDirectoryDoesNotExist($home.'/templates/namaku');
    }

    public function test_template_delete_protected_builtin_fails(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'delete', 'laravel', '--force']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('built-in TERPROTEKSI', $output);
    }

    public function test_template_delete_without_force_on_non_interactive_fails(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home, 'namaku');

        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'delete', 'namaku']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Terminal non-interaktif', $output);
        $this->assertDirectoryExists($home.'/templates/namaku');
    }

    public function test_template_invalid_subcommand_shows_usage(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['template', 'bogus']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Subcommand template tidak dikenal', $output);
        $this->assertStringContainsString('template create <nama>', $output);
    }
}
