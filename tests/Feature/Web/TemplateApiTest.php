<?php

namespace Lace\Ainstruct\Tests\Feature\Web;

use Illuminate\Container\Container;
use Lace\Ainstruct\Bootstrap\AppServiceProvider;
use Lace\Ainstruct\Tests\TestCase;
use Lace\Ainstruct\Web\Kernel;
use Lace\Ainstruct\Web\Request;
use Lace\Ainstruct\Web\Response;

final class TemplateApiTest extends TestCase
{
    private string $home;

    protected function setUp(): void
    {
        parent::setUp();

        $this->home = $this->tempDir();
        $this->withHome($this->home);
        $this->installMinimalTemplate($this->home);
        chdir($this->tempDir());
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

    public function test_list_templates_returns_builtin_and_custom(): void
    {
        [$status, $payload] = $this->call('GET', '/api/templates');

        $this->assertSame(200, $status);
        $this->assertTrue($payload['ok']);
        $this->assertNotEmpty($payload['data']['builtin']);
        $this->assertContains('minimal', array_column($payload['data']['custom'], 'name'));
    }

    public function test_detail_custom_is_editable(): void
    {
        [$status, $payload] = $this->call('GET', '/api/templates/minimal');

        $this->assertSame(200, $status);
        $this->assertTrue($payload['data']['editable']);
        $this->assertSame('custom', $payload['data']['origin']);
    }

    public function test_detail_builtin_is_not_editable(): void
    {
        [$status, $payload] = $this->call('GET', '/api/templates/laravel');

        $this->assertSame(200, $status);
        $this->assertFalse($payload['data']['editable']);
        $this->assertSame('builtin', $payload['data']['origin']);
    }

    public function test_detail_unknown_template_returns_404(): void
    {
        [$status, $payload] = $this->call('GET', '/api/templates/tidak-ada');

        $this->assertSame(404, $status);
        $this->assertFalse($payload['ok']);
    }

    public function test_tree_lists_entries(): void
    {
        [$status, $payload] = $this->call('GET', '/api/templates/minimal/tree');

        $this->assertSame(200, $status);
        $this->assertContains('ai-instructions', array_column($payload['data']['entries'], 'name'));
    }

    public function test_read_file_returns_content(): void
    {
        [$status, $payload] = $this->call('GET', '/api/templates/minimal/file', [], ['path' => 'ai-instructions.md']);

        $this->assertSame(200, $status);
        $this->assertStringContainsString('Minimal AI Instructions', $payload['data']['content']);
    }

    public function test_read_file_traversal_rejected(): void
    {
        [$status, $payload] = $this->call('GET', '/api/templates/minimal/file', [], ['path' => '../rahasia.md']);

        $this->assertSame(422, $status);
        $this->assertFalse($payload['ok']);
    }

    public function test_write_file_custom_succeeds(): void
    {
        [$status] = $this->call('PUT', '/api/templates/minimal/file', [
            'path' => 'ai-instructions.md',
            'content' => "# Konstitusi baru\n",
        ]);

        $this->assertSame(200, $status);

        [$status, $payload] = $this->call('GET', '/api/templates/minimal/file', [], ['path' => 'ai-instructions.md']);

        $this->assertSame(200, $status);
        $this->assertSame("# Konstitusi baru\n", $payload['data']['content']);
    }

    public function test_write_file_builtin_forbidden(): void
    {
        [$status, $payload] = $this->call('PUT', '/api/templates/laravel/file', [
            'path' => 'ai-instructions.md',
            'content' => 'ubah',
        ]);

        $this->assertSame(403, $status);
        $this->assertFalse($payload['ok']);
    }

    public function test_write_file_rejects_binary_content(): void
    {
        [$status, $payload] = $this->call('PUT', '/api/templates/minimal/file', [
            'path' => 'ai-instructions.md',
            'content' => "teks\x00biner",
        ]);

        $this->assertSame(422, $status);
        $this->assertFalse($payload['ok']);
    }

    public function test_delete_requires_force(): void
    {
        [$status] = $this->call('DELETE', '/api/templates/minimal');

        $this->assertSame(409, $status);

        [$status] = $this->call('DELETE', '/api/templates/minimal', ['force' => true]);

        $this->assertSame(200, $status);
        $this->assertDirectoryDoesNotExist($this->home.'/templates/minimal');
    }

    public function test_delete_builtin_forbidden_even_with_force(): void
    {
        [$status, $payload] = $this->call('DELETE', '/api/templates/laravel', ['force' => true]);

        $this->assertSame(403, $status);
        $this->assertFalse($payload['ok']);
    }

    public function test_create_scaffolds_template(): void
    {
        [$status, $payload] = $this->call('POST', '/api/templates', ['name' => 'baru']);

        $this->assertSame(201, $status);
        $this->assertSame('baru', $payload['data']['name']);
        $this->assertFileExists($this->home.'/templates/baru/ai-instructions.md');
    }

    public function test_create_requires_name(): void
    {
        [$status, $payload] = $this->call('POST', '/api/templates', []);

        $this->assertSame(422, $status);
        $this->assertFalse($payload['ok']);
    }

    public function test_clone_copies_custom_template(): void
    {
        [$status] = $this->call('POST', '/api/templates/salinan/clone', ['source' => 'minimal']);

        $this->assertSame(201, $status);
        $this->assertFileExists($this->home.'/templates/salinan/ai-instructions.md');
        $this->assertFileExists($this->home.'/templates/salinan/ai-instructions/01-intro.md');
    }

    public function test_update_requires_force_and_pulls_builtin_source(): void
    {
        [$status] = $this->call('PUT', '/api/templates/minimal');

        $this->assertSame(409, $status);

        [$status] = $this->call('PUT', '/api/templates/minimal', ['force' => true, 'from' => 'laravel']);

        $this->assertSame(200, $status);
        $this->assertFileExists($this->home.'/templates/minimal/ai-instructions.md');
    }

    public function test_origin_guard_rejects_foreign_origin_on_mutation(): void
    {
        [$status, $payload] = $this->call('POST', '/api/templates', ['name' => 'x'], [], [
            'origin' => 'https://evil.example',
        ]);

        $this->assertSame(403, $status);
        $this->assertFalse($payload['ok']);
    }

    public function test_origin_guard_accepts_localhost_origin(): void
    {
        [$status] = $this->call('POST', '/api/templates', ['name' => 'aman'], [], [
            'origin' => 'http://127.0.0.1:8787',
        ], ['SERVER_PORT' => '8787']);

        $this->assertSame(201, $status);
    }

    public function test_unknown_route_returns_404(): void
    {
        [$status, $payload] = $this->call('GET', '/api/teplates');

        $this->assertSame(404, $status);
        $this->assertFalse($payload['ok']);
    }

    public function test_wrong_method_returns_405(): void
    {
        [$status] = $this->call('DELETE', '/api/templates/minimal/tree');

        $this->assertSame(405, $status);
    }
}
