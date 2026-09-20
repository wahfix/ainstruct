<?php

namespace Lace\Ainstruct\Tests\Feature\Console;

use Lace\Ainstruct\Tests\TestCase;

final class HelpCommandTest extends TestCase
{
    public function test_no_args_lists_available_commands(): void
    {
        [$exit, $output] = $this->runCapture($this->makeApplication(), []);

        $plain = $this->stripAnsi($output);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Available commands', $plain);
        $this->assertStringContainsString('distribute <framework>', $plain);
        $this->assertStringContainsString('init', $plain);
        $this->assertStringContainsString('status', $plain);
        $this->assertStringContainsString('reset <framework>', $plain);
        $this->assertStringContainsString('wipe', $plain);
        $this->assertStringContainsString('template', $plain);
        $this->assertStringContainsString('help', $plain);
    }

    public function test_help_argument_lists_available_commands(): void
    {
        [$exit, $output] = $this->runCapture($this->makeApplication(), ['help']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Available commands', $this->stripAnsi($output));
    }

    public function test_dash_help_flag_lists_available_commands(): void
    {
        [$exit, $output] = $this->runCapture($this->makeApplication(), ['--help']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Available commands', $this->stripAnsi($output));
    }

    public function test_dash_h_flag_lists_available_commands(): void
    {
        [$exit, $output] = $this->runCapture($this->makeApplication(), ['-h']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Available commands', $this->stripAnsi($output));
    }

    public function test_help_command_shows_detail(): void
    {
        [$exit, $output] = $this->runCapture($this->makeApplication(), ['help', 'status']);

        $plain = $this->stripAnsi($output);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Fungsi', $plain);
        $this->assertStringContainsString('ainstruct status [--json]', $plain);
        $this->assertStringContainsString('Exit code', $plain);
    }

    public function test_help_template_lists_subcommands(): void
    {
        [$exit, $output] = $this->runCapture($this->makeApplication(), ['help', 'template']);

        $plain = $this->stripAnsi($output);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Subcommands', $plain);
        $this->assertStringContainsString('clone <nama> <sumber> [--force]', $plain);
        $this->assertStringContainsString('path <nama>', $plain);
    }

    public function test_help_unknown_command_fails(): void
    {
        [$exit, $output] = $this->runCapture($this->makeApplication(), ['help', 'nonsense']);

        $plain = $this->stripAnsi($output);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Perintah tidak dikenal', $plain);
    }
}
