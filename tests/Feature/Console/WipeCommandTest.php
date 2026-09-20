<?php

namespace Lace\Ainstruct\Tests\Feature\Console;

use Lace\Ainstruct\Tests\TestCase;

final class WipeCommandTest extends TestCase
{
    public function test_wipe_removes_all_artifacts_with_force(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home);

        $project = $this->tempDir();

        $artifacts = [
            'AGENTS.md',
            'CLAUDE.md',
            'GEMINI.md',
            '.github/copilot-instructions.md',
            '.cursorrules',
            '.windsurfrules',
            '.continuerules',
            '.aider.conf.yml',
            'opencode.json',
            '.cursor/rules/minimal-directives.mdc',
            '.clinerules/minimal-directives.md',
            '.opencode/config.json',
            'ai-instructions/master/ai-instructions.md',
        ];

        foreach ($artifacts as $artifact) {
            $path = $project.'/'.$artifact;
            $dir = dirname($path);

            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($path, 'fixture');
        }

        $app = $this->makeApplication();
        chdir($project);

        [$exit, $output] = $this->runCapture($app, ['wipe', '--force']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Akan menghapus dari:', $output);
        $this->assertStringContainsString('Wipe selesai.', $output);

        foreach ($artifacts as $artifact) {
            $this->assertFileDoesNotExist($project.'/'.$artifact, "harus terhapus: {$artifact}");
        }

        $this->assertDirectoryDoesNotExist($project.'/.cursor');
        $this->assertDirectoryDoesNotExist($project.'/.clinerules');
        $this->assertDirectoryDoesNotExist($project.'/.opencode');
        $this->assertDirectoryDoesNotExist($project.'/ai-instructions');
    }

    public function test_wipe_without_force_on_non_interactive_fails(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $project = $this->tempDir();
        file_put_contents($project.'/AGENTS.md', 'fixture');

        $app = $this->makeApplication();
        chdir($project);

        [$exit, $output] = $this->runCapture($app, ['wipe']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Terminal non-interaktif', $output);
        $this->assertFileExists($project.'/AGENTS.md', 'tidak boleh terhapus tanpa --force');
    }

    public function test_wipe_when_no_artifacts_reports_nothing(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $project = $this->tempDir();

        $app = $this->makeApplication();
        chdir($project);

        [$exit, $output] = $this->runCapture($app, ['wipe', '--force']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Tidak ada artefak instruksi yang ditemukan', $output);
    }
}
