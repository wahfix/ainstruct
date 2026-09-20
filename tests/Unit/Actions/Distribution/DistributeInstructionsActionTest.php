<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Tests\Unit\Actions\Distribution;

use Lace\Ainstruct\Actions\Distribution\DistributeInstructionsAction;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\MasterRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Tests\TestCase;
use Lace\Ainstruct\Values\Template;
use PHPUnit\Framework\Attributes\Test;

final class DistributeInstructionsActionTest extends TestCase
{
    #[Test]
    public function it_distributes_all_artifacts(): void
    {
        $template = new Template('laravel', '/fake/templates/laravel', TemplateOrigin::BUILTIN);

        $templates = $this->createMock(TemplateRepositoryContract::class);
        $templates->method('findOrFail')->with('laravel')->willReturn($template);

        $masters = $this->createMock(MasterRepositoryContract::class);
        $masters->method('syncTo')->willReturn(['constitution' => 'created', 'module' => 'skipped']);

        $files = $this->createMock(InstructionFileRepositoryContract::class);
        $files->method('isDirectory')->willReturn(false);
        $files->method('distributeOpenCodeDir')->willReturn([]);

        $paths = new Paths('/fake/target', '/fake/pkg');
        $action = new DistributeInstructionsAction($templates, $masters, $files, $paths);

        $result = $action->handle(['template' => 'laravel', 'target_dir' => '/fake/target']);

        $this->assertSame('laravel', $result->framework);
        $this->assertSame('builtin', $result->templateSource);
        $this->assertCount(11, $result->files);
        $this->assertCount(3, $result->notes);
    }

    #[Test]
    public function it_throws_when_template_does_not_exist(): void
    {
        $this->expectException(TemplateNotFoundException::class);

        $templates = $this->createMock(TemplateRepositoryContract::class);
        $templates->method('findOrFail')->willThrowException(new TemplateNotFoundException('nope', ['laravel']));

        $masters = $this->createMock(MasterRepositoryContract::class);
        $files = $this->createMock(InstructionFileRepositoryContract::class);

        $action = new DistributeInstructionsAction($templates, $masters, $files, new Paths('/fake/target', '/fake/pkg'));

        $action->handle(['template' => 'nope', 'target_dir' => '/fake/target']);
    }

    #[Test]
    public function it_throws_when_no_template_specified(): void
    {
        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage('Tidak ada framework yang ditentukan');

        $templates = $this->createMock(TemplateRepositoryContract::class);
        $templates->method('names')->willReturn(['laravel']);

        $masters = $this->createMock(MasterRepositoryContract::class);
        $files = $this->createMock(InstructionFileRepositoryContract::class);

        $action = new DistributeInstructionsAction($templates, $masters, $files, new Paths('/fake/target', '/fake/pkg'));

        $action->handle(['template' => null, 'target_dir' => '/fake/target']);
    }
}
