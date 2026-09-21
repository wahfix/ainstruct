<?php

namespace Lace\Ainstruct\Tests\Unit\Services\Template;

use Lace\Ainstruct\Repositories\InstructionFileRepository;
use Lace\Ainstruct\Services\Template\SourceImporter;
use Lace\Ainstruct\Support\Filesystem;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Tests\TestCase;

final class SourceImporterTest extends TestCase
{
    private SourceImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importer = new SourceImporter(
            new InstructionFileRepository(new Paths(sys_get_temp_dir(), sys_get_temp_dir()), new Filesystem)
        );
    }

    public function test_is_git_source_accepts_urls_and_local_paths(): void
    {
        foreach ([
            'https://github.com/user/repo',
            'https://github.com/user/repo.git',
            'ssh://git@github.com/user/repo.git',
            'git@github.com:user/repo.git',
            '/home/user/templates/repo',
            './repo',
            '../repo',
            '~/repo',
            'github.com/user/repo.git',
        ] as $source) {
            $this->assertTrue($this->importer->isGitSource($source), "harus git source: {$source}");
        }
    }

    public function test_is_git_source_rejects_template_names(): void
    {
        foreach ([
            'laravel',
            'vanilla-php',
            'my-template',
            '',
        ] as $source) {
            $this->assertFalse($this->importer->isGitSource($source), "bukan git source: {$source}");
        }
    }
}
