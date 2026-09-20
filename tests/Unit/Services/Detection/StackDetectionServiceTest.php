<?php

namespace Lace\Ainstruct\Tests\Unit\Services\Detection;

use Lace\Ainstruct\Enums\DetectionConfidence;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Repositories\InstructionFileRepository;
use Lace\Ainstruct\Services\Detection\StackDetectionService;
use Lace\Ainstruct\Support\Filesystem;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Tests\TestCase;
use Lace\Ainstruct\Values\Template;

final class StackDetectionServiceTest extends TestCase
{
    public function test_evaluate_without_detect_file_returns_unknown(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $template = new Template('custom', $home, TemplateOrigin::CUSTOM);

        $service = $this->service();

        $result = $service->evaluate($template, $home);

        $this->assertSame(0, $result->score);
        $this->assertSame(DetectionConfidence::UNKNOWN, $result->confidence);
        $this->assertSame([], $result->signals);
    }

    public function test_evaluate_matches_file_and_grep_signals(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $templateDir = $home.'/templates/minimal';

        mkdir($templateDir, 0777, true);
        file_put_contents(
            $templateDir.'/ainstruct-detect.txt',
            "5|file|artisan|CLI artisan milik Laravel\n4|grep|composer.json:laravel/framework|Dependency composer 'laravel/framework'\n"
        );

        $project = $home.'/project';

        mkdir($project, 0777, true);
        file_put_contents($project.'/artisan', "#!/usr/bin/env php\n");
        file_put_contents($project.'/composer.json', '{"require":{"laravel/framework":"^12.0"}}');

        $template = new Template('minimal', $templateDir, TemplateOrigin::BUILTIN);

        $result = $this->service()->evaluate($template, $project);

        $this->assertSame(9, $result->score);
        $this->assertSame(DetectionConfidence::CONFIRMED, $result->confidence);
        $this->assertSame(['CLI artisan milik Laravel', "Dependency composer 'laravel/framework'"], $result->signals);
    }

    public function test_evaluate_ignores_comments_and_malformed_lines(): void
    {
        $home = $this->tempDir();
        $this->withHome($home);

        $templateDir = $home.'/templates/minimal';

        mkdir($templateDir, 0777, true);
        file_put_contents(
            $templateDir.'/ainstruct-detect.txt',
            "# komentar\n\n2|dir|app|Direktori app\nmalformed\n"
        );

        $project = $home.'/project';

        mkdir($project.'/app', 0777, true);

        $template = new Template('minimal', $templateDir, TemplateOrigin::BUILTIN);

        $result = $this->service()->evaluate($template, $project);

        $this->assertSame(2, $result->score);
        $this->assertSame(DetectionConfidence::WEAK, $result->confidence);
        $this->assertSame(['Direktori app'], $result->signals);
    }

    public function test_confidence_boundaries(): void
    {
        $this->assertSame(DetectionConfidence::CONFIRMED, DetectionConfidence::fromScore(6));
        $this->assertSame(DetectionConfidence::CONFIRMED, DetectionConfidence::fromScore(10));
        $this->assertSame(DetectionConfidence::STRONG, DetectionConfidence::fromScore(4));
        $this->assertSame(DetectionConfidence::STRONG, DetectionConfidence::fromScore(5));
        $this->assertSame(DetectionConfidence::WEAK, DetectionConfidence::fromScore(2));
        $this->assertSame(DetectionConfidence::WEAK, DetectionConfidence::fromScore(3));
        $this->assertSame(DetectionConfidence::UNKNOWN, DetectionConfidence::fromScore(1));
        $this->assertSame(DetectionConfidence::UNKNOWN, DetectionConfidence::fromScore(0));
    }

    private function service(): StackDetectionService
    {
        return new StackDetectionService(
            new InstructionFileRepository(
                Paths::fromEnvironment(),
                new Filesystem,
            )
        );
    }
}
