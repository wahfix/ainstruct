<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Tests\Unit\Actions\Distribution;

use Lace\Ainstruct\Actions\Distribution\GetStatusAction;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Tests\TestCase;
use Lace\Ainstruct\Values\Template;
use PHPUnit\Framework\Attributes\Test;

final class GetStatusActionTest extends TestCase
{
    #[Test]
    public function it_reports_not_distributed_without_master(): void
    {
        $templates = $this->createMock(TemplateRepositoryContract::class);
        $templates->method('all')->willReturn([]);

        $files = $this->createMock(InstructionFileRepositoryContract::class);
        $files->method('fileExists')->willReturn(false);
        $files->method('isFile')->willReturn(false);
        $files->method('isDirectory')->willReturn(false);
        $files->method('moduleDirHasMdFiles')->willReturn(false);
        $files->method('isOpenCodeCliInstalled')->willReturn(false);

        $action = new GetStatusAction($templates, $files, new Paths('/fake/target', '/fake/pkg'));

        $status = $action->handle(['target_dir' => '/fake/target']);

        $this->assertSame('not-distributed', $status->status);
        $this->assertFalse($status->masterExists);
        $this->assertSame(0, $status->artifactsPresent);
        $this->assertGreaterThan(0, $status->issues);
        $this->assertNull($status->activeTemplate);
    }

    #[Test]
    public function it_reports_healthy_when_everything_synced(): void
    {
        $template = new Template('minimal', '/fake/templates/minimal', TemplateOrigin::CUSTOM);

        $templates = $this->createMock(TemplateRepositoryContract::class);
        $templates->method('all')->willReturn([$template]);

        $files = $this->createMock(InstructionFileRepositoryContract::class);
        $files->method('fileExists')->willReturnCallback(
            fn (string $path): bool => str_ends_with($path, '/ai-instructions/master/ai-instructions.md')
                || str_ends_with($path, '/.aider.conf.yml')
                || str_ends_with($path, '/opencode.json')
        );
        $files->method('isFile')->willReturn(true);
        $files->method('isDirectory')->willReturn(true);
        $files->method('filesIdentical')->willReturn(true);
        $files->method('mdcMatchesMaster')->willReturn(true);
        $files->method('moduleDirHasMdFiles')->willReturn(true);
        $files->method('directoriesDifferIgnoringLeftOnlyMaster')->willReturn(false);
        $files->method('isOpenCodeCliInstalled')->willReturn(false);

        $action = new GetStatusAction($templates, $files, new Paths('/fake/target', '/fake/pkg'));

        $status = $action->handle(['target_dir' => '/fake/target']);

        $this->assertSame('ok', $status->status);
        $this->assertSame('minimal', $status->activeTemplate);
        $this->assertSame('custom', $status->templateSource);
        $this->assertSame(13, $status->artifactsTotal);
        $this->assertSame(13, $status->artifactsPresent);
        $this->assertSame(0, $status->issues);
        $this->assertEmpty($status->missing);
        $this->assertEmpty($status->outOfSync);
    }
}
