<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Tests\Feature\Console;

use Lace\Ainstruct\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ApplicationStatusTest extends TestCase
{
    #[Test]
    public function status_json_reports_not_distributed_in_empty_dir(): void
    {
        $target = $this->tempDir();
        $this->withHome($this->tempDir());

        chdir($target);
        [$exit, $output] = $this->runCapture($this->makeApplication(), ['status', '--json']);

        $this->assertSame(1, $exit);

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertSame('not-distributed', $data['status']);
        $this->assertSame(0, $data['artifacts_present']);
        $this->assertGreaterThan(0, $data['issues']);

        // Output murni JSON: tanpa warna ANSI dan tanpa header.
        $this->assertStringNotContainsString("\033", $output);
        $this->assertStringNotContainsString('AI Instructions Distribution Script', $output);
    }

    #[Test]
    public function status_json_reports_ok_after_distribute(): void
    {
        $target = $this->tempDir();
        $home = $this->tempDir();
        $this->withHome($home);
        $this->installMinimalTemplate($home);

        chdir($target);
        $application = $this->makeApplication();

        [$distributeExit] = $this->runCapture($application, ['minimal']);
        $this->assertSame(0, $distributeExit);

        [$statusExit, $output] = $this->runCapture($application, ['status', '--json']);
        $this->assertSame(0, $statusExit);

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertSame('ok', $data['status']);
        $this->assertSame('minimal', $data['active_template']);
        $this->assertSame('custom', $data['template_source']);
        $this->assertSame(13, $data['artifacts_total']);
        $this->assertSame(13, $data['artifacts_present']);
        $this->assertSame(0, $data['issues']);
        $this->assertFalse($data['master_customized']);
        $this->assertArrayHasKey('missing', $data);
        $this->assertArrayHasKey('out_of_sync', $data);
        $this->assertArrayHasKey('module_out_of_sync', $data);
    }
}
