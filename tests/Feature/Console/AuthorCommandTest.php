<?php

namespace Lace\Ainstruct\Tests\Feature\Console;

use Lace\Ainstruct\Tests\TestCase;

final class AuthorCommandTest extends TestCase
{
    public function test_author_requires_name(): void
    {
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['author', '--input', '/tmp']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('--name', $this->stripAnsi($output));
    }

    public function test_author_requires_input(): void
    {
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['author', '--name', 'laravel']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('--input', $this->stripAnsi($output));
    }

    public function test_author_rejects_unknown_mode(): void
    {
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['author', '--mode', 'bogus', '--name', 'x', '--input', '/tmp']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Mode tidak dikenal', $this->stripAnsi($output));
    }

    public function test_author_docs_mode_not_available_yet(): void
    {
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['author', '--mode', 'docs', '--name', 'x', '--input', 'https://example.com']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('belum tersedia', $this->stripAnsi($output));
    }

    public function test_author_multi_mode_not_available_yet(): void
    {
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['author', '--mode', 'multi', '--name', 'x', '--input', '/tmp']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('belum tersedia', $this->stripAnsi($output));
    }

    public function test_author_input_must_be_existing_directory(): void
    {
        $app = $this->makeApplication();

        [$exit, $output] = $this->runCapture($app, ['author', '--name', 'x', '--input', '/tmp/nihil-tak-ada']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('bukan direktori', $this->stripAnsi($output));
    }

    public function test_author_dry_run_prints_plan(): void
    {
        $app = $this->makeApplication();
        $dir = $this->tempDir();

        [$exit, $output] = $this->runCapture($app, ['author', '--mode', 'repo', '--name', 'laravel', '--input', $dir, '--dry-run']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Rencana authoring', $this->stripAnsi($output));
        $this->assertStringContainsString('repo', $this->stripAnsi($output));
        $this->assertStringContainsString('laravel', $this->stripAnsi($output));
        $this->assertStringContainsString('templates/laravel', $this->stripAnsi($output));
        $this->assertStringContainsString('Guard: authoring TIDAK pernah menebar artefak', $this->stripAnsi($output));
    }

    public function test_author_dry_run_accepts_spec_flag(): void
    {
        $app = $this->makeApplication();
        $dir = $this->tempDir();

        [$exit, $output] = $this->runCapture($app, ['author', '--mode', 'repo', '--name', 'x', '--input', $dir, '--spec', $dir.'/spec.md', '--dry-run']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Fase 4', $this->stripAnsi($output));
    }

    public function test_author_without_dry_run_requires_later_phase(): void
    {
        $app = $this->makeApplication();
        $dir = $this->tempDir();

        [$exit, $output] = $this->runCapture($app, ['author', '--name', 'laravel', '--input', $dir]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('belum tersedia', $this->stripAnsi($output));
    }
}
