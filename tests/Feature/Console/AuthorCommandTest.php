<?php

namespace Lace\Ainstruct\Tests\Feature\Console;

use Lace\Ainstruct\Tests\TestCase;

final class AuthorCommandTest extends TestCase
{
    private string $stateHome;

    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateHome = $this->tempDir();
        $this->workDir = $this->tempDir();

        putenv('AINSTRUCT_STATE_HOME='.$this->stateHome);
        chdir($this->workDir);
    }

    protected function tearDown(): void
    {
        putenv('AINSTRUCT_STATE_HOME');
        putenv('AINSTRUCT_OPENCODE_BIN');
        putenv('AINSTRUCT_FAKE_LOG');
        putenv('AINSTRUCT_FAKE_OUTPUT_DIR');

        parent::tearDown();
    }

    private function enableFakeOpencode(?string $outputDir = null): string
    {
        $fakeBin = $this->tempDir().'/opencode';
        copy($this->fixture('bin/fake-opencode'), $fakeBin);
        chmod($fakeBin, 0777);
        putenv('AINSTRUCT_OPENCODE_BIN='.$fakeBin);

        if ($outputDir !== null) {
            putenv('AINSTRUCT_FAKE_OUTPUT_DIR='.$outputDir);
        }

        return $fakeBin;
    }

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

        [$exit, $output] = $this->runCapture($app, ['author', '--mode', 'multi', '--name', 'x', '--input', 'https://example.com']);

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

    public function test_author_dry_run_prints_prompt(): void
    {
        $app = $this->makeApplication();
        $dir = $this->tempDir();

        [$exit, $output] = $this->runCapture($app, ['author', '--mode', 'repo', '--name', 'laravel', '--input', $dir, '--dry-run']);

        $plain = $this->stripAnsi($output);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Prompt sesi agent', $plain);
        $this->assertStringContainsString('ARCHITECT-GUIDE.md', $plain);
        $this->assertStringContainsString('mode repo', $plain);
    }

    public function test_author_dry_run_accepts_spec_flag(): void
    {
        $app = $this->makeApplication();
        $dir = $this->tempDir();

        [$exit, $output] = $this->runCapture($app, ['author', '--mode', 'repo', '--name', 'x', '--input', $dir, '--spec', $dir.'/spec.md', '--dry-run']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Fase 4', $this->stripAnsi($output));
    }

    public function test_author_run_without_opencode_binary_fails(): void
    {
        $app = $this->makeApplication();
        $dir = $this->tempDir();

        // Pastikan resolveOrFail() melempar: env tidak diset + opencode tidak
        // ditemukan di PATH (PATH dikosongkan untuk determinisme).
        putenv('AINSTRUCT_OPENCODE_BIN');
        $originalPath = (string) getenv('PATH');
        putenv('PATH='.$this->tempDir());

        try {
            [$exit, $output] = $this->runCapture($app, ['author', '--name', 'laravel', '--input', $dir]);
        } finally {
            putenv('PATH='.$originalPath);
        }

        $plain = $this->stripAnsi($output);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('opencode tidak ditemukan', $plain);
    }

    public function test_author_run_executes_session_and_verifies_framework(): void
    {
        $app = $this->makeApplication();
        $dir = $this->tempDir();
        $outputDir = $dir.'/templates/laravel';
        $fakeLog = $dir.'/argv.log';

        $this->enableFakeOpencode($outputDir);
        putenv('AINSTRUCT_FAKE_LOG='.$fakeLog);

        [$exit, $output] = $this->runCapture($app, ['author', '--name', 'laravel', '--input', $dir, '--output', $outputDir]);

        $plain = $this->stripAnsi($output);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Sesi agent dimulai', $plain);
        $this->assertStringContainsString('Sesi selesai.', $plain);
        $this->assertStringContainsString('Verifikasi kerangka', $plain);
        $this->assertStringContainsString('ai-instructions.md', $plain);
        $this->assertStringContainsString('Set instruksi siap', $plain);
        $this->assertFileExists($outputDir.'/ai-instructions.md');
        $this->assertStringContainsString('ARCHITECT-GUIDE.md', (string) file_get_contents($fakeLog));
    }

    public function test_author_run_reports_missing_framework(): void
    {
        $app = $this->makeApplication();
        $dir = $this->tempDir();
        $outputDir = $dir.'/templates/laravel';

        // Fake tanpa output dir: sesi selesai tapi tidak menulis kerangka.
        $this->enableFakeOpencode(null);

        [$exit, $output] = $this->runCapture($app, ['author', '--name', 'laravel', '--input', $dir, '--output', $outputDir]);

        $plain = $this->stripAnsi($output);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('belum lengkap', $plain);
        $this->assertFileDoesNotExist($outputDir.'/ai-instructions.md');
    }
}
