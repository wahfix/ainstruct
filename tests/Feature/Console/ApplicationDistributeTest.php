<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Tests\Feature\Console;

use Lace\Ainstruct\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ApplicationDistributeTest extends TestCase
{
    #[Test]
    public function it_distributes_all_artifacts_from_custom_template(): void
    {
        $target = $this->tempDir();
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home);

        chdir($target);
        $application = $this->makeApplication();

        [$exit] = $this->runCapture($application, ['minimal']);

        $this->assertSame(0, $exit);

        $this->assertFileExists($target.'/AGENTS.md');
        $this->assertFileExists($target.'/CLAUDE.md');
        $this->assertFileExists($target.'/GEMINI.md');
        $this->assertFileExists($target.'/.github/copilot-instructions.md');
        $this->assertFileExists($target.'/.cursorrules');
        $this->assertFileExists($target.'/.cursor/rules/minimal-directives.mdc');
        $this->assertFileExists($target.'/.windsurfrules');
        $this->assertFileExists($target.'/.clinerules/minimal-directives.md');
        $this->assertFileExists($target.'/.continuerules');
        $this->assertFileExists($target.'/.aider.conf.yml');
        $this->assertFileExists($target.'/opencode.json');
        $this->assertFileExists($target.'/ai-instructions/master/ai-instructions.md');
        $this->assertFileExists($target.'/ai-instructions/01-intro.md');

        // Konstitusi di AGENTS.md identik dengan fixture.
        $this->assertSame(
            file_get_contents($this->fixture('templates/minimal/ai-instructions.md')),
            file_get_contents($target.'/AGENTS.md')
        );
    }

    #[Test]
    public function it_writes_cursor_mdc_with_frontmatter_and_master_body(): void
    {
        $target = $this->tempDir();
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home);

        chdir($target);
        [$exit] = $this->runCapture($this->makeApplication(), ['minimal']);
        $this->assertSame(0, $exit);

        $mdc = (string) file_get_contents($target.'/.cursor/rules/minimal-directives.mdc');
        $constitution = (string) file_get_contents($this->fixture('templates/minimal/ai-instructions.md'));

        $this->assertStringStartsWith(
            "---\ndescription: \"minimal AI Instructions — Directives for AI Agent\"\nglobs: \"**/*\"\nalwaysApply: true\n",
            $mdc
        );
        $this->assertStringEndsWith($constitution, $mdc);
    }

    #[Test]
    public function it_writes_aider_config_with_module_list(): void
    {
        $target = $this->tempDir();
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home);

        chdir($target);
        [$exit] = $this->runCapture($this->makeApplication(), ['minimal']);
        $this->assertSame(0, $exit);

        $aider = (string) file_get_contents($target.'/.aider.conf.yml');

        $this->assertStringContainsString('# minimal — Aider Configuration', $aider);
        $this->assertStringContainsString('read:', $aider);
        $this->assertStringContainsString('- ai-instructions/README.md', $aider);
        $this->assertStringContainsString('- ai-instructions/01-intro.md', $aider);
    }

    #[Test]
    public function it_exits_with_error_when_template_not_found(): void
    {
        $target = $this->tempDir();
        $this->withHome($this->tempDir());

        chdir($target);
        [$exit, $output] = $this->runCapture($this->makeApplication(), ['unknown-framework']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Framework/template directory tidak ditemukan: unknown-framework', $output);
    }
}
