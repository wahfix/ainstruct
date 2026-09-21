<?php

namespace Lace\Ainstruct\Tests\Feature\Web;

use Illuminate\Container\Container;
use Lace\Ainstruct\Bootstrap\AppServiceProvider;
use Lace\Ainstruct\Tests\TestCase;
use Lace\Ainstruct\Web\Kernel;
use Lace\Ainstruct\Web\Request;
use Lace\Ainstruct\Web\Response;

final class OpencodeApiTest extends TestCase
{
    private string $stateHome;

    private string $fakeBin;

    private string $projectDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateHome = $this->tempDir();
        putenv('AINSTRUCT_STATE_HOME='.$this->stateHome);

        $this->fakeBin = $this->tempDir().'/opencode';
        copy($this->fixture('bin/fake-opencode'), $this->fakeBin);
        chmod($this->fakeBin, 0777);
        putenv('AINSTRUCT_OPENCODE_BIN='.$this->fakeBin);

        $this->projectDir = realpath($this->tempDir()) ?: $this->tempDir();
        chdir($this->projectDir);
    }

    protected function tearDown(): void
    {
        putenv('AINSTRUCT_STATE_HOME');
        putenv('AINSTRUCT_OPENCODE_BIN');
        putenv('AINSTRUCT_FAKE_LOG');
        putenv('AINSTRUCT_FAKE_SLEEP');

        parent::tearDown();
    }

    private function kernel(): Kernel
    {
        $container = new Container;
        (new AppServiceProvider($container))->register();

        return new Kernel($container);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $server
     * @return array{0: int, 1: array<string, mixed>, 2: Response}
     */
    private function call(string $method, string $path, array $body = [], array $query = [], array $headers = [], array $server = []): array
    {
        $request = new Request($method, Request::normalizePath($path), $query, $body, $headers, $server);
        $response = $this->kernel()->handle($request);

        return [$response->status, json_decode($response->body, true) ?: [], $response];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array<string, string>  $headers
     * @return array{0: int, 1: array<string, mixed>, 2: Response}
     */
    private function startSession(array $overrides = [], array $headers = []): array
    {
        $payload = array_merge([
            'directory' => $this->projectDir,
            'prompt' => 'Perbaiki bug dan tambahkan test',
        ], $overrides);

        return $this->call('POST', '/api/opencode/sessions', $payload, [], $headers);
    }

    private function waitUntil(callable $condition, string $message, int $maxMs = 6000, int $stepMs = 50): void
    {
        $deadline = microtime(true) + $maxMs / 1000;

        while (microtime(true) < $deadline) {
            if ($condition()) {
                return;
            }

            usleep($stepMs * 1000);
        }

        $this->fail($message);
    }

    public function test_status_reports_missing_binary(): void
    {
        putenv('AINSTRUCT_OPENCODE_BIN='.$this->projectDir.'/tidak-ada');

        [$status, $payload] = $this->call('GET', '/api/opencode/status');

        $this->assertSame(200, $status);
        $this->assertFalse($payload['data']['available']);
        $this->assertNull($payload['data']['version']);
        $this->assertSame($this->projectDir, $payload['data']['defaultDirectory']);
    }

    public function test_status_reports_version_when_available(): void
    {
        [$status, $payload] = $this->call('GET', '/api/opencode/status');

        $this->assertSame(200, $status);
        $this->assertTrue($payload['data']['available']);
        $this->assertSame('fake opencode v9.9.9', $payload['data']['version']);
        $this->assertSame($this->fakeBin, $payload['data']['bin']);
        $this->assertSame($this->projectDir, $payload['data']['defaultDirectory']);
    }

    public function test_start_requires_directory_and_prompt(): void
    {
        [$status, $payload] = $this->call('POST', '/api/opencode/sessions', []);

        $this->assertSame(422, $status);
        $this->assertStringContainsString('directory', $payload['error']);

        [$status, $payload] = $this->call('POST', '/api/opencode/sessions', ['directory' => $this->projectDir]);

        $this->assertSame(422, $status);
        $this->assertStringContainsString('prompt', $payload['error']);

        [$status] = $this->startSession(['prompt' => "   \n"]);

        $this->assertSame(422, $status);
    }

    public function test_start_rejects_missing_directory(): void
    {
        [$status, $payload] = $this->startSession(['directory' => $this->projectDir.'/tidak-ada']);

        $this->assertSame(422, $status);
        $this->assertFalse($payload['ok']);
    }

    public function test_start_rejects_invalid_continue_session_id(): void
    {
        [$status, $payload] = $this->startSession(['session' => '../etc/passwd']);

        $this->assertSame(422, $status);
        $this->assertStringContainsString('session', $payload['error']);
    }

    public function test_start_runs_session_and_writes_output(): void
    {
        $fakeLog = $this->tempDir().'/argv.log';
        putenv('AINSTRUCT_FAKE_LOG='.$fakeLog);

        [$status, $payload] = $this->startSession([
            'model' => 'provider/model',
            'agent' => 'build',
            'auto' => true,
            'session' => 'ses_abc123',
        ]);

        $this->assertSame(201, $status);
        $this->assertStringStartsWith('webui-', (string) $payload['data']['id']);
        $this->assertSame($this->projectDir, $payload['data']['directory']);
        $this->assertSame('Perbaiki bug dan tambahkan test', $payload['data']['prompt']);

        $id = $payload['data']['id'];

        $this->waitUntil(
            fn (): bool => ($this->call('GET', '/api/opencode/sessions/'.$id)[1]['data']['meta']['status'] ?? null) === 'finished',
            'Sesi tidak selesai dalam batas waktu.'
        );

        [$status, $payload] = $this->call('GET', '/api/opencode/sessions/'.$id);

        $this->assertSame(200, $status);
        $this->assertSame('finished', $payload['data']['meta']['status']);
        $this->assertStringContainsString('FAKE-RUN: menerima instruksi', $payload['data']['output']['content']);

        $lines = array_values(array_filter(explode("\n", (string) file_get_contents($fakeLog))));

        $this->assertContains('run', $lines);
        $this->assertContains('--model', $lines);
        $this->assertContains('provider/model', $lines);
        $this->assertContains('--agent', $lines);
        $this->assertContains('build', $lines);
        $this->assertContains('--session', $lines);
        $this->assertContains('ses_abc123', $lines);
        $this->assertContains('--auto', $lines);
        $this->assertSame('Perbaiki bug dan tambahkan test', $lines[array_key_last($lines)]);

        [$status, $payload] = $this->call('GET', '/api/opencode/sessions');

        $this->assertSame(200, $status);
        $this->assertContains($id, array_column($payload['data']['sessions'], 'id'));
    }

    public function test_start_reports_running_then_stop_marks_stopped(): void
    {
        putenv('AINSTRUCT_FAKE_SLEEP=60');

        [$status, $payload] = $this->startSession();

        $this->assertSame(201, $status);
        $this->assertSame('running', $payload['data']['status']);

        $id = $payload['data']['id'];

        [$status, $payload] = $this->call('POST', '/api/opencode/sessions/'.$id.'/stop');

        $this->assertSame(200, $status);
        $this->assertSame('stopped', $payload['data']['status']);

        [$status, $payload] = $this->call('GET', '/api/opencode/sessions/'.$id);

        $this->assertSame(200, $status);
        $this->assertSame('stopped', $payload['data']['meta']['status']);
    }

    public function test_delete_requires_force_and_removes_session(): void
    {
        [$status, $payload] = $this->startSession();
        $id = $payload['data']['id'];

        $this->waitUntil(
            fn (): bool => ($this->call('GET', '/api/opencode/sessions/'.$id)[1]['data']['meta']['status'] ?? null) === 'finished',
            'Sesi tidak selesai dalam batas waktu.'
        );

        [$status] = $this->call('DELETE', '/api/opencode/sessions/'.$id);

        $this->assertSame(409, $status);

        [$status] = $this->call('DELETE', '/api/opencode/sessions/'.$id, ['force' => true]);

        $this->assertSame(200, $status);

        [$status] = $this->call('GET', '/api/opencode/sessions/'.$id);

        $this->assertSame(404, $status);

        $this->assertDirectoryDoesNotExist($this->stateHome.'/ainstruct/webui/opencode/'.$id);
    }

    public function test_delete_unknown_session_returns_404(): void
    {
        [$status, $payload] = $this->call('DELETE', '/api/opencode/sessions/webui-tidak-ada', ['force' => true]);

        $this->assertSame(404, $status);
        $this->assertFalse($payload['ok']);
    }

    public function test_list_is_empty_before_any_session(): void
    {
        [$status, $payload] = $this->call('GET', '/api/opencode/sessions');

        $this->assertSame(200, $status);
        $this->assertSame([], $payload['data']['sessions']);
        $this->assertSame($this->projectDir, $payload['data']['defaultDirectory']);
    }

    public function test_origin_guard_rejects_foreign_origin_on_start(): void
    {
        [$status, $payload] = $this->startSession([], [
            'origin' => 'https://evil.example',
        ]);

        $this->assertSame(403, $status);
        $this->assertFalse($payload['ok']);
    }
}
