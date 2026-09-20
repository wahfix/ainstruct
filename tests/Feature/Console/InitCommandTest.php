<?php

namespace Lace\Ainstruct\Tests\Feature\Console;

use Lace\Ainstruct\Tests\TestCase;

final class InitCommandTest extends TestCase
{
    private function laravelProject(string $root): string
    {
        $project = $root.'/project';

        mkdir($project, 0777, true);
        file_put_contents($project.'/artisan', "#!/usr/bin/env php\n");
        file_put_contents($project.'/composer.json', '{"require":{"laravel/framework":"^12.0"}}');

        return $project;
    }

    public function test_init_detects_minimal_and_distributes(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home, 'minimal');

        $project = $this->laravelProject($this->tempDir());

        $app = $this->makeApplication();
        chdir($project);

        [$exit, $output] = $this->runCapture($app, ['init']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Deteksi teknologi di:', $output);
        $this->assertStringContainsString('minimal', $output);
        $this->assertStringContainsString('★ terpilih', $output);
        $this->assertStringContainsString('Selesai!', $output);
        $this->assertFileExists($project.'/ai-instructions/master/ai-instructions.md');
        $this->assertFileExists($project.'/AGENTS.md');
    }

    public function test_init_with_forced_template_ignores_detection(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home, 'minimal');

        $project = $this->tempDir().'/project';
        mkdir($project, 0777, true);

        $app = $this->makeApplication();
        chdir($project);

        [$exit, $output] = $this->runCapture($app, ['init', '--template', 'minimal']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Mode paksa: memakai template minimal', $this->stripAnsi($output));
        $this->assertStringContainsString('Selesai!', $output);
        $this->assertFileExists($project.'/AGENTS.md');
    }

    public function test_init_dry_run_does_not_change_anything(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home, 'minimal');

        $project = $this->laravelProject($this->tempDir());

        $app = $this->makeApplication();
        chdir($project);

        [$exit, $output] = $this->runCapture($app, ['init', '--dry-run']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Dry-run', $output);
        $this->assertFileDoesNotExist($project.'/AGENTS.md');
        $this->assertDirectoryDoesNotExist($project.'/ai-instructions');
    }

    public function test_init_without_signals_fails(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home, 'minimal');

        $project = $this->tempDir().'/project';
        mkdir($project, 0777, true);

        $app = $this->makeApplication();
        chdir($project);

        [$exit, $output] = $this->runCapture($app, ['init']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Tidak ada sinyal teknologi terdeteksi', $output);
    }

    public function test_init_with_unknown_forced_template_fails(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $project = $this->tempDir().'/project';
        mkdir($project, 0777, true);

        $app = $this->makeApplication();
        chdir($project);

        [$exit, $output] = $this->runCapture($app, ['init', '--template', 'nihil']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('nihil', $output);
    }

    public function test_init_custom_template_shadows_builtin(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        // Custom 'lerel' memiliki detect file sendiri yang lebih sedikit sinyalnya;
        // dipastikan custom diprioritaskan bila skor sama/lebih tinggi dibanding built-in.
        $this->installMinimalTemplate($home, 'lerel');

        $project = $this->laravelProject($this->tempDir());

        $app = $this->makeApplication();
        chdir($project);

        [$exit, $output] = $this->runCapture($app, ['init']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('lerel', $output);
    }
}
