<?php

namespace Lace\Ainstruct\Tests\Unit\Services\Author;

use Illuminate\Container\Container;
use Lace\Ainstruct\Bootstrap\AppServiceProvider;
use Lace\Ainstruct\Services\Author\AuthorRunnerService;
use Lace\Ainstruct\Tests\TestCase;

final class AuthorRunnerServiceTest extends TestCase
{
    private function runner(): AuthorRunnerService
    {
        $container = new Container;
        (new AppServiceProvider($container))->register();

        return $container->get(AuthorRunnerService::class);
    }

    public function test_compose_repo_prompt_mentions_playbook_and_guards(): void
    {
        $prompt = $this->runner()->composeRepoPrompt('laravel', '/tmp/repo', '/tmp/out/templates/laravel');

        $this->assertStringContainsString('mode repo', $prompt);
        $this->assertStringContainsString('ARCHITECT-GUIDE.md', $prompt);
        $this->assertStringContainsString('/tmp/repo', $prompt);
        $this->assertStringContainsString('/tmp/out/templates/laravel', $prompt);
        $this->assertStringContainsString('Klausa 1', $prompt);
        $this->assertStringContainsString('REFERENCE BAR', $prompt);
        $this->assertStringContainsString('templates/laravel/', $prompt);
    }

    public function test_verify_reports_incomplete_framework(): void
    {
        $outputDir = $this->tempDir().'/templates/laravel';
        mkdir($outputDir, 0777, true);

        $result = $this->runner()->verify($outputDir);

        $this->assertFalse($result['ok']);
        $this->assertCount(3, $result['checks']);
        $this->assertFalse($result['checks'][0]['passed']);
        $this->assertFalse($result['checks'][1]['passed']);
        $this->assertFalse($result['checks'][2]['passed']);
    }

    public function test_verify_accepts_framework_with_constitution_and_module(): void
    {
        $outputDir = $this->tempDir().'/templates/laravel';
        mkdir($outputDir.'/ai-instructions', 0777, true);
        file_put_contents($outputDir.'/ai-instructions.md', "# Konstitusi\n");
        file_put_contents($outputDir.'/ai-instructions/01-governance.md', "# Modul\n");

        $result = $this->runner()->verify($outputDir);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['checks'][0]['passed']);
        $this->assertTrue($result['checks'][1]['passed']);
        $this->assertTrue($result['checks'][2]['passed']);
    }
}
