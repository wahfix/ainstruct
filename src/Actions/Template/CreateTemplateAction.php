<?php

namespace Lace\Ainstruct\Actions\Template;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Actions\RuledActionContract;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Values\Template;

final class CreateTemplateAction extends Action implements RuledActionContract
{
    private const SCAFFOLD_CONSTITUTION = <<<'MD'
# AI INSTRUCTION SYSTEM — CONSTITUTION

> [!CRITICAL]
> Template scaffold dibuat lewat `template create` — TERBUKA untuk diedit konsumen.
> Bangun set instruksi presisi di sini, lalu distribusikan dengan
> `ainstruct <nama-template>`.

# File Map

- `ai-instructions.md`  ← konstitusi (entry point). Tulis prinsip, priority system,
                          rule scope, workflow wajib, quality gates, referensi cepat.
- `ai-instructions/`    ← modul bernomor (`01-governance.md`, `02-agent-workflow.md`, …).
                          Referensikan setiap modul dari konstitusi.

# Workflow Wajib

1. Wajib baca `MASTER_BUILD_SPECIFICATION.md` di root proyek sebelum menulis kode.
2. Aturan ditulis MUST / MUST NOT yang actionable, spesifik, dan berdasar bukti proyek target.
3. Verifikasi sendiri sebelum "selesai": lint, health check, dan uji distribusi.
MD;

    private const SCAFFOLD_MODULE = <<<'MD'
# Modul Instruksi (scaffold)

Buat modul bernomor di direktori ini (contoh: `01-governance.md`, `02-agent-workflow.md`)
dan referensikan dari `ai-instructions.md` di atas.
MD;

    private const SCAFFOLD_DETECT = <<<'TXT'
# ainstruct-detect.txt — sinyal pendeteksi template ini (dibaca oleh `ainstruct init`).
# Format tiap baris: <bobot>|<tipe>|<argumen>|<label>
#   tipe 'file': argumen = path relatif; cocok bila file ada (contoh: 5|file|artisan|CLI)
#   tipe 'dir' : argumen = path relatif; cocok bila direktori ada.
#   tipe 'grep': argumen = <path>:<pola regex>; cocok bila file ada & memuat pola.
# Bobot 1–5; keyakinan: skor >=6 CONFIRMED, >=4 STRONG, >=2 WEAK, sisanya UNKNOWN.
# Hapus komentar lalu isi sinyal nyata template Anda, contoh:
# 5|file|artisan|CLI artisan milik Laravel
# 4|grep|composer.json:vendor/framework|Dependency composer vendor/framework
TXT;

    public function __construct(
        private TemplateRepositoryContract $templates,
        private InstructionFileRepositoryContract $files,
    ) {}

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'pattern:/^[A-Za-z0-9_-]+$/'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    protected function handler(array $payload): Template
    {
        $name = $payload['name'];
        $force = (bool) ($payload['force'] ?? false);

        $target = $this->templates->consumerPathFor($name);

        if ($this->files->isDirectory($target)) {
            throw new InvalidOperationException("Template custom sudah ada: {$target}");
        }

        $conflict = $this->templates->find($name);

        if ($conflict !== null && ! $force) {
            throw new InvalidOperationException(
                "Nama '{$name}' sudah dipakai oleh: {$conflict->directory}\n".
                '   Pakai nama lain, atau --force untuk mengambil alih nama (shadow built-in).'
            );
        }

        $this->files->writeFile($target.'/ai-instructions.md', self::SCAFFOLD_CONSTITUTION);
        $this->files->writeFile($target.'/ai-instructions/README.md', self::SCAFFOLD_MODULE);
        $this->files->writeFile($target.'/ainstruct-detect.txt', self::SCAFFOLD_DETECT);

        return new Template($name, $target, TemplateOrigin::CUSTOM);
    }
}
