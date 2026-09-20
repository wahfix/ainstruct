<?php

namespace Lace\Ainstruct\Bootstrap;

use Illuminate\Container\Container;
use Lace\Ainstruct\Contracts\Detection\StackDetectorContract;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\MasterRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Repositories\InstructionFileRepository;
use Lace\Ainstruct\Repositories\MasterRepository;
use Lace\Ainstruct\Repositories\TemplateRepository;
use Lace\Ainstruct\Services\Detection\StackDetectionService;
use Lace\Ainstruct\Support\Filesystem;
use Lace\Ainstruct\Support\Paths;

final class AppServiceProvider
{
    public function __construct(private Container $container) {}

    /**
     * Bind kontrak ke implementasi konkret. Repository dan aksi lain
     * di-resolve otomatis oleh container (auto-wiring).
     */
    public function register(): void
    {
        $this->container->singleton(Paths::class, fn (): Paths => Paths::fromEnvironment());
        $this->container->singleton(Filesystem::class);

        $this->container->singleton(TemplateRepositoryContract::class, TemplateRepository::class);
        $this->container->singleton(MasterRepositoryContract::class, MasterRepository::class);
        $this->container->singleton(InstructionFileRepositoryContract::class, InstructionFileRepository::class);
        $this->container->singleton(StackDetectorContract::class, StackDetectionService::class);
    }
}
