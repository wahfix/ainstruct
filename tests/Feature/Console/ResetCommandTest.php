<?php

namespace Lace\Ainstruct\Tests\Feature\Console;

use Lace\Ainstruct\Tests\TestCase;

final class ResetCommandTest extends TestCase
{
    public function test_reset_removes_master_and_redistributes(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home);

        $project = $this->tempDir();

        // Sebelum: install hasil distribute.
        $app = $this->makeApplication();
        chdir($project);
        $this->runCapture($app, ['minimal']);

        $masterConstitution = $project.'/ai-instructions/master/ai-instructions.md';

        $this->assertFileExists($masterConstitution);

        // Modifikasi master seolah-olah user sudah mengedit.
        file_put_contents($masterConstitution, "MASTER EDITED\n");

        [$exit, $output] = $this->runCapture($app, ['reset', 'minimal']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Reset: master rules removed. Re-distributing...', $output);
        $this->assertStringContainsString('minimal', $output);

        $freshMaster = (string) file_get_contents($masterConstitution);
        $this->assertStringNotContainsString('MASTER EDITED', $freshMaster, 'master harus tersegarkan dari template');
        $this->assertStringContainsString('Minimal AI Instructions', $freshMaster);
        $this->assertFileExists($project.'/AGENTS.md');
    }

    public function test_reset_without_framework_shows_frameworks(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $project = $this->tempDir();

        $app = $this->makeApplication();
        chdir($project);

        [$exit, $output] = $this->runCapture($app, ['reset']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Frameworks tersedia:', $output);
    }
}
