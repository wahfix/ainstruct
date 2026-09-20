<?php

namespace Lace\Ainstruct\Tests\Unit\Console;

use Lace\Ainstruct\Console\Input;
use Lace\Ainstruct\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class InputTest extends TestCase
{
    #[Test]
    public function it_reads_positionals_and_flags(): void
    {
        $input = new Input(['laravel', '--force']);

        $this->assertSame('laravel', $input->firstPositional());
        $this->assertTrue($input->hasFlag('--force'));
        $this->assertFalse($input->hasFlag('--json'));
    }

    #[Test]
    public function it_reads_flag_value_separated_and_equals(): void
    {
        $input = new Input(['--from', 'digi', '--template=laravel']);

        $this->assertSame('digi', $input->flagValue('--from'));
        $this->assertSame('laravel', $input->flagValue('--template'));
        $this->assertNull($input->flagValue('--missing'));
    }

    #[Test]
    public function it_ignores_flags_in_positional_index(): void
    {
        $input = new Input(['--json', 'status']);

        $this->assertSame('status', $input->firstPositional());
    }
}
