# AI-Instructions

<p align="center">
  <img src="assets/logo.svg" alt="AI-INSTRUCTIONS — Instruction Architecture" width="340">
</p>

<p align="center">
  <a href="https://packagist.org/packages/lace/ainstruct"><img src="https://img.shields.io/packagist/v/lace/ainstruct.svg" alt="Packagist Version"></a>
  <a href="https://packagist.org/packages/lace/ainstruct"><img src="https://img.shields.io/packagist/dt/lace/ainstruct.svg" alt="Packagist Downloads"></a>
  <a href="https://packagist.org/packages/lace/ainstruct"><img src="https://img.shields.io/packagist/php-v/lace/ainstruct.svg" alt="PHP Version"></a>
  <a href="https://github.com/wahfix/ainstruct/actions"><img src="https://github.com/wahfix/ainstruct/workflows/tests/badge.svg" alt="Tests"></a>
  <a href="https://github.com/wahfix/ainstruct/actions"><img src="https://github.com/wahfix/ainstruct/workflows/lint/badge.svg" alt="Lint"></a>
</p>

**Repository Instruction Architect — mesin adaptif (proyek meta) pengelola set instruksi AI.**

Repositori ini BUKAN proyek konsumen teknologi apa pun. Ini adalah **mesin adaptif**:
sistem yang **memproduksi, menguji, mendistribusikan, dan mengadaptasi set instruksi AI**
yang presisi untuk digunakan oleh AI coding agent di **proyek konsumen** (mis. LingSID),
dan yang setiap salinannya membawa mekanisme yang sama dengan operator miliknya sendiri
(memori + repo privat GitHub + sinkronisasi dua arah).

Baca **`VISION.md`** untuk visi lengkap tiga lapisan (artefak → pabrik → mesin adaptif)
dan siklus hidup instruksi.

## Repo Ini Bukan Tempat Distribusi

`ainstruct` (engine PHP di `bin/ainstruct`, paket Composer `lace/ainstruct`) menjalankan distribusi
ke **arah `pwd`** (direktori tempat CLI dieksekusi). Menjalankannya di repo ini akan menimpa `AGENTS.md` (self-instruction arsitek)
dan memunculkan artefak distribusi (`CLAUDE.md`, `GEMINI.md`, `.cursorrules`, `ai-instructions/`,
dll.) di root dengan isi hasil-generate. **Itu KESALAHAN KRITIS — KEGAGALAN TOTAL.**
CLI hanya dijalankan di root **proyek konsumen**.

## Layout Repository

```
AI-Instructions/
├── AGENTS.md            ← Self-instruction arsitek (peran, larangan, aturan git)
├── ARCHITECT-GUIDE.md   ← Playbook (wajib dibaca penuh sebelum bekerja)
├── VISION.md            ← Visi meta: mesin adaptif tiga lapisan (artefak→pabrik→adaptif)
├── bin/                 ← CLI & distribusi: ainstruct (engine PHP — paket Composer lace/ainstruct)
├── scripts/             ← Quality gates: health-check, antislop-check, install-hooks
├── .gitignore           ← Mencegah artefak distribusi ter-commit ke repo ini
├── .opencode/           ← SELF-HOSTING: skill anti-slop + team-authoring + agent plenger
├── operator-memory/     ← MESIN ADAPTIF: skill + backup dua arah + bootstrap per-salinan
└── templates/           ← Set instruksi per framework (satu folder per framework/teknologi)
    ├── laravel/         ← Set exemplar (berakar pada LingSID; rincian di bawah)
    │   ├── ai-instructions.md            ← Konstitusi (entry point)
    │   └── ai-instructions/
    │       ├── 01-…-21-*.md                          ← Modul universal (01–11 + skill 13–21)
    │       ├── 12-project-specific/                  ← Invarian per proyek
    │       │   ├── lingusid.md                       ← Invarian proyek LingSID
    │       │   └── canonical-snippets.md             ← Bank snippet verbatim + anchor
    │       └── README.md
    └── vanilla-php/     ← Set PHP vanilla (konstitusi + modul 01–21 + 12-project-specific/)
        ├── ai-instructions.md            ← Konstitusi (entry point)
        └── ai-instructions/
            ├── 01-…-21-*.md                          ← Modul universal (01–11 + skill 13–21)
            ├── 12-project-specific/                  ← Kanonik template + bank snippet
            │   ├── template-baseline.md              ← Keputusan kanonik template vanilla-php
            │   └── canonical-snippets.md             ← Bank snippet verbatim + anchor
            └── README.md
```

## Cara Kerja

1. **Template set hidup di `templates/<Framework>/`** (mis. `templates/laravel/`) — ini sumber
   kebenaran untuk editing. Isinya dibangun dari analisis nyata sebuah proyek referensi:
   - `templates/laravel/` berakar pada proyek **LingSID** (Laravel 12 + Inertia/Vue 3 + TypeScript):
     aturan arsitektur, coding standards, naming, testing, security, git, tools, quality gates
     semuanya berbukti dari kode nyata proyek.
   - `canonical-snippets.md` menampung potongan kode **verbatim** + `path:line` sebagai
     "DNA" format yang harus ditiru IDENTIK oleh agent masa depan, termasuk penandaan
     pola rusak/legacy (`// BAD`) yang dilarang ditiru.
2. **Distribusi dilakukan di proyek konsumen**, bukan di repo ini:

   ```bash
   # di root proyek konsumen (mis. /home/ubuntu/Project/WahyuLingu/lingusid)
   ainstruct laravel
   # atau langsung dari checkout repo ini (setelah composer install):
   ./bin/ainstruct laravel
   ```

   Script menyalin konstitusi ke `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`,
   `.github/copilot-instructions.md`, `.cursorrules`, `.cursor/rules/laravel-directives.mdc`,
   `.windsurfrules`, `.clinerules/laravel-directives.md`, `.continuerules`,
   `.aider.conf.yml`, dan modul ke `ai-instructions/`.

   > **Bukan template**: tim development multi-agent (`team-dev`) dan filter anti-AI-slop
   > (`antislop`) adalah **komponen sistem repo ini**, bukan template terdistribusi. Tim
   > development hidup sebagai protokol internal authoring (skill `team-authoring` +
   > subagent di `.opencode/agents/`); filter anti-AI-slop hidup sebagai skill self-hosting
   > (`.opencode/skills/antislop*`) yang digunakan agent penulis dan ditegakkan gate
   > `scripts/antislop-check.sh` (bagian dari health-check). Konsumen yang membutuhkan
   > filter anti-slop di proyeknya menyalin sistem vendor `.opencode/skills/antislop*`
   > dari repo ini (upstream MIT `miqdadbadjuber/anti-slop`) ke `.opencode/skills/`
   > proyeknya — panduan pemakaian ada di modul `10-quality-gates.md` set yang didistribusikan.
   >
   > **Integrasi anti-slop**: filter anti-slop di-vendor dan diintegrasikan ke repo ini pada
   > posisi commit `3f6f333` (commit anti-slop terakhir, termerge di `0f08e63`);
   > sumber upstream: [miqdadbadjuber/anti-slop](https://github.com/miqdadbadjuber/anti-slop)
   > (MIT, © 2026 Miqdad Badjuber).

3. **opencode dipasang sebagai default AI untuk pekerjaan**: selain `AGENTS.md`
   (dibaca otomatis oleh opencode), script menulis `opencode.json` di root proyek
   konsumen yang menu: `default_agent: "build"` + `instructions` dari `AGENTS.md`.
   Jika CLI opencode belum terpasang, script menampilkan perintah pemasangan resmi
   (`curl -fsSL https://opencode.ai/install | bash`).
4. **Master dapat di-custom**: script membuat `ai-instructions/master/` di proyek konsumen
   dan TIDAK menimpanya bila sudah ada — spesialisasi proyek dilakukan di sana, lalu script
   dijalankan ulang untuk mendistribusikan versi custom.
5. **Self-instruction arsitek mengadopsi aturan kualitas `templates/laravel/`**: modul universal
   dari set `templates/laravel/ai-instructions/` yang berlaku untuk kerja AI apa pun
   (evidence-anchored authoring, quality gates + senior self-review, edge probes authoring,
   change impact analysis, debug disipliner, agent discipline, reproduce-everywhere) diadopsi
   ke `AGENTS.md` bagian 5 dan di-enforce lewat pre-commit hook + CI (`scripts/health-check.sh`,
   markdownlint, smoke test).

## Reset & Wipe di Proyek Konsumen

`ainstruct` juga mendukung dua perintah untuk mengelola state instruksi **di
proyek konsumen** (dieksekusi dari root proyek konsumen, arah `pwd`):

- **Reset ke default** — buang seluruh custom di `ai-instructions/master/`, bangun ulang
  dari template framework, lalu distribusikan ulang. Sama dengan alur manual
  `rm -rf ai-instructions/master && ainstruct <framework>`:

  ```bash
  ainstruct reset laravel
  ```

- **Wipe** — hapus SEMUA artefak instruksi dari proyek konsumen: `AGENTS.md`, `CLAUDE.md`,
  `GEMINI.md`, `.github/copilot-instructions.md`, `.cursorrules`, `.cursor/`,
  `.windsurfrules`, `.clinerules/`, `.continuerules`, `.aider.conf.yml`, dan `ai-instructions/`
  (termasuk `master/` hasil custom):

  ```bash
  ainstruct wipe          # tanpa --force: diminta konfirmasi
  ainstruct wipe --force  # untuk automation/CI tanpa prompt
  ```

Keduanya dijalankan dari root proyek konsumen — **bukan** dari repo authoring ini.

## Status: Periksa Kesehatan Instruksi

`ainstruct status` memeriksa state instruksi AI di direktori saat ini (pwd) **tanpa
mengubah apa pun** — laporkan template aktif, artefak yang hilang atau berbeda dari
master, dan langkah perbaikan. Filosofinya sama dengan `git status`: tahu kondisi
sebelum bertindak.

```bash
ainstruct status             # laporan untuk manusia (berwarna)
ainstruct status --json      # laporan JSON murni (untuk automation/CI)
```

- **Template aktif** — template mana yang master-nya cocok (custom menang atas
  built-in bila sama), plus sumber direktori; master yang di-custom ditandai
  sebagai `custom` (fitur normal — edit aman, dipertahankan saat redistribute).
- **Artefak** — semua file hasil distribusi dicek hadir & sinkron dengan master
  (AGENTS.md, CLAUDE.md, GEMINI.md, `.cursorrules`, `.windsurfrules`,
  `.continuerules`, `.github/copilot-instructions.md`, Cursor `.mdc`, Cline,
  `.aider.conf.yml`, `opencode.json`, dan modul `ai-instructions/`).
- **Exit code untuk automation**: `0` = sehat (artefak lengkap & sinkron), `1` =
  ada masalah (belum didistribusikan / artefak hilang / tidak sinkron).
- **Deteksi manual edit**: file hasil distribusi yang diedit langsung (mis.
  `AGENTS.md`) terdeteksi sebagai *out of sync* — perbaikan dengan
  `ainstruct <framework>` (custom di `ai-instructions/master/` tetap dipertahankan).

Contoh pemakaian dalam CI: jalankan `ainstruct status --json`, lalu gagalkan build
bila `"status"` bukan `"ok"` — setiap proyek selalu punya instruksi yang sinkron.

## Adaptor: Composer (satu-satunya kanal distribusi)

CLI `ainstruct` didistribusikan sebagai **paket Composer** `lace/ainstruct`. Repo ini
menyediakan satu adaptor agar CLI bisa dipakai **langsung di proyek konsumen** (arah `pwd`)
tanpa menyalin repo secara manual. Semua subcommand berfungsi sama: `distribute`, `reset`,
`wipe`, `status`, `init`, `template`.

```bash
composer global require lace/ainstruct
ainstruct laravel          # distribusikan framework
ainstruct reset laravel    # kembali ke default template
ainstruct wipe --force     # hapus semua artefak instruksi
ainstruct status --json    # laporan JSON untuk automation/CI
```

Paket `lace/ainstruct` memasang bin `ainstruct` (symlink `vendor/bin/ainstruct`
→ `bin/ainstruct`, entry PHP yang me-resolve autoload & path akar paket). Untuk
pengembangan, repo ini juga bisa dipakai langsung: `composer install`, lalu
`./bin/ainstruct <subcommand>`.

## Init: Deteksi Stack & Scaffold Otomatis

`ainstruct init` mendeteksi teknologi proyek di direktori saat ini lalu memilih dan
mendistribusikan template yang paling cocok — tanpa perlu tahu nama template lebih dulu.
Deteksi berbasis **sinyal** dari `ainstruct-detect.txt` milik setiap template
(built-in maupun custom; custom menang atas built-in bila nama sama):

```bash
ainstruct init                 # deteksi → konfirmasi → distribusi template terbaik
ainstruct init --dry-run       # hanya laporan deteksi, tanpa mengubah apa pun
ainstruct init --template laravel --force   # paksa template, tanpa deteksi/konfirmasi
```

- **Ukuran keyakinan** mengikuti filosofi "deteksi, jangan tebak": tiap sinyal punya
  bobot 1–5; skor < 6 = CONFIRMED, 4–5 = STRONG, 2–3 = WEAK, 0–1 = UNKNOWN.
- **Tidak pernah menebak**: bila tidak ada template yang cocok, perintah gagal (exit 1)
  dan mengarahkan ke `init --template <nama>` / `ainstruct <nama>`.
- **`ainstruct-detect.txt`** adalah berkas opsional per template, format baris
  `<bobot>|<tipe>|<argumen>|<label>` — tipe `file`, `dir`, atau `grep`
  (`<path>:<pola regex>`). Template `templates/laravel/` (Laravel + Inertia/Vue 3 + TypeScript)
  dan `templates/vanilla-php/` (PHP vanilla + Composer PSR-4 + PHPUnit/PHPStan) sudah memuat
  detektornya; `template create` membuat starter kosong yang bisa diisi.
- Opsi `--force` dibutuhkan di lingkungan non-interaktif/CI untuk melewati konfirmasi.

## Template Manager: Template Milik Konsumen

Konsumen dapat membuat/memiliki template sendiri secara instan — tanpa menunggu
repo authoring menambah template — lalu menghapus/memperbaruinya lewat perintah.
Template **built-in** (ship bersama paket, mis. `templates/laravel/`) **TERPROTEKSI**:
tidak bisa dihapus atau diubah/diperbarui langsung; untuk menyesuaikannya, konsumen
**wajib clone sebagai template miliknya** lalu mengedit salinannya.

Template konsumen tersimpan di `AINSTRUCT_HOME` (`${XDG_CONFIG_HOME:-$HOME/.config}/ainstruct`
secara default, atau override `AINSTRUCT_HOME` untuk isolasi/CI) dan menang atas built-in
bila namanya sama (shadow — bisa dibatalkan dengan menghapus template custom).

```bash
ainstruct template list                          # daftar built-in (proteksi) + custom
ainstruct template create myfw                   # buat template/scaffold sendiri
ainstruct template create myfw --from https://github.com/user/myfw   # impor template dari git
ainstruct template clone mylaravel laravel       # customisasi built-in → milik Anda
ainstruct template update mylaravel --from laravel   # tarik ulang dari built-in
ainstruct template update myfw                   # tanpa --from: tarik dari sumber tersimpan
ainstruct template delete myfw --force           # hapus template custom (built-in DITOLAK)
ainstruct template path mylaravel                # lokasi direktori (untuk diedit)
```

- `create` membuat scaffold kosong (`ai-instructions.md` konstitusi + modul) yang
  terbuka diedit; dengan `--from <sumber>` ia mengimpor template dari **sumber git**
  (URL `https`/`ssh` atau jalur repo git lokal) — berguna untuk berbagi template
  antar proyek/mesin tanpa menunggu repo ini. `clone` menyalin template
  (built-in atau custom) sebagai milik Anda. `update` menimpa salinan Anda dari
  sumber (`--from <sumber>` untuk sumber lain; tanpa `--from`, template yang pernah
  diimpor ditarik ulang dari **sumber tersimpan**). `delete` menghapus template
  custom; built-in selalu DITOLAK dengan pesan arahkan ke clone.
- Sumber git dan ref (`--ref <branch|tag>`) dicatat ke file `ainstruct.source` di
  dalam folder template — `update` tanpa `--from` membaca file ini. Impor butuh
  perintah `git`; hasil impor adalah salinan tanpa `.git/` (bukan repo kerja).
- Operasi destruktif (`delete`, `update`) butuh konfirmasi `[y/N]`; di lingkungan
  non-interaktif/CI wajib `--force`.
- Setelah template custom tersedia, distribusikan seperti biasa: `ainstruct myfw`.

## Operator Memory — Mesin Adaptif di Setiap Salinan

Setiap **salinan repository ini** (fork, clone, atau distribusi via adaptor)
membawa mekanisme `operator-memory/` yang sama: sebuah skill yang membuat agent
belajar meniru **operator salinan tersebut** — identitas, gaya, preferensi, pola
keputusan — lalu menyimpannya di `~/.config/opencode/skills/operator-memory/persona.md` (siapa operator)
dan `~/.config/opencode/skills/operator-memory/context.md` (di mana kita)
yang di-sinkronkan **dua arah** ke repo privat GitHub operator.

Mekanisme bersifat **per-salinan**: operator A di salinan A punya memori, repo
backup, dan GitHub sendiri; operator B di salinan B membangun identitasnya
sendiri. `operator-memory/backup.sh` tidak pernah menimpa — selalu tarik lalu
gabung.

```bash
# di setiap salinan, oleh operator salinan tersebut:
./operator-memory/bootstrap-operator-memory.sh
# non-interaktif:
./operator-memory/bootstrap-operator-memory.sh --name "Nama" --email "x@y.id" --github handle
```

Script membuat repo privat GitHub `operator-persona` (via `gh`), memasang skill +
memori ke `~/.config/opencode/`, menautkan `opencode.jsonc`, dan menyiapkan
`operator-memory/backup.sh` (pull → merge 3-arah → push) +
`operator-memory/restore.sh`. Detail di `operator-memory/README.md`.

## Membuat Set Instruksi Baru

Ikuti `ARCHITECT-GUIDE.md` secara penuh (ringkasannya):

1. Baca playbook `ARCHITECT-GUIDE.md`.
2. Eksplorasi repository target (protocol eksplorasi 7 fase, termasuk koleksi snippet kanonik).
3. Analisis (coding style, peletakan file, model fitur, testing, error handling, dll.).
4. Pelajari `templates/laravel/` sebagai **reference bar** — target kualitas minimum (bagian 6D playbook).
5. Buat folder `templates/<Framework>/` dengan struktur konstitusi + modul 01–11 + skill kualitas 13–21 +
   `12-project-specific/`.
6. Tulis dengan evidence anchors + snippet kanonik verbatim.
7. Verifikasi diri (bagian 9 playbook).
8. Jalankan distribusi dari **root proyek konsumen**.
9. Laporkan ringkasan ke operator.

## Klausa Wajib Set Hasil Generasi

Setiap set WAJIB memuat KLAUSA 1–5 (detail penuh di `ARCHITECT-GUIDE.md` bagian 6):

1. **Khusus proyek target** — tanpa hubungan/referensi ke repository contoh mana pun.
2. **Dilarang kerja langsung di branch `main`** — wajib branch baru.
3. **Commit message ringkas** mengikuti format standar proyek.
4. **Inisiasi git wajib** bila proyek baru belum repository git.
5. **Protokol `MASTER_BUILD_SPECIFICATION.md`** — wajib dibaca sebelum kode apa pun; jika
   tidak ada, agent wajib BERHENTI, bertanya ke operator secara mendetil, lalu membuatnya
   secara lengkap, detil, dan presisi sebelum menulis kode (LEVEL 2; pelanggaran =
   KEGAGALAN TOTAL).

## Larangan Mutlak (di Repo Ini)

- **DILARANG** menjalankan `./bin/ainstruct` di root repo ini.
- **DILARANG** men-commit artefak distribusi (AGENTS.md isi hasil-generate, CLAUDE.md,
  GEMINI.md, .cursorrules, .windsurfrules, .continuerules, .clinerules/, .cursor/rules/,
  .github/, .aider.conf.yml, ai-instructions/) ke repo ini.
- **DILARANG** bekerja, commit, push, atau merge langsung di branch `main` (protected).
  Semua perubahan masuk `main` hanya via PR yang disetujui operator.

## Git Conventions

- **Proteksi `main`**: tidak ada push/commit/merge langsung ke `main`; kerja di branch
  pendek (`feat/…`, `fix/…`, `docs/…`, `chore/…`) dari `main`, lalu PR ke `main`.
- Commit ringkas dalam bahasa Inggris, verb-prefixed (mis. `Add canonical snippet bank`).
- Perubahan besar ditawarkan dulu ke operator ("commit + push?") dan menunggu persetujuan.
- Sebelum commit: cek `git status`, `git diff`, `git log --oneline -10`.

## Status Saat Ini

| Set | Status | Catatan |
|-----|--------|---------|
| `templates/laravel/` | Aktif | Berakar pada LingSID; konstitusi + modul 01–21 + invariant proyek di `12-project-specific/lingusid.md` + bank snippet kanonik; memuat protokol MASTER_BUILD_SPECIFICATION. Self-instruction arsitek (`AGENTS.md` §5) mengadopsi aturan kualitas universal dari set ini. |
| `templates/vanilla-php/` | Aktif | Set PHP vanilla (konstitusi + modul 01–21 + kanonik template di `12-project-specific/template-baseline.md` + bank snippet kanonik) + detektor `ainstruct-detect.txt`; masuk pada rilis v0.2.5. |
| `operator-memory/` | Aktif | **MESIN ADAPTIF (lapisan 3)**: skill `operator-memory` + memori live (`~/.config/opencode/...`) + script backup dua arah, restore, dan bootstrap (`operator-memory/`) — setiap salinan repo me-bootstrap operatornya masing-masing ke repo privat GitHub. |
| `templates/java/`, `templates/react/` | Direncanakan | Didukung script (coming soon), folder belum dibuat |

> **Bukan template** — komponen sistem repo ini: tim development multi-agent (protokol internal
> authoring via skill `team-authoring` + subagent `.opencode/agents/`) dan filter anti-AI-slop
> (`scripts/antislop-check.sh` + skill `.opencode/skills/antislop*`, self-hosting, upstream MIT
> `miqdadbadjuber/anti-slop`). Keduanya dipakai mesin authoring (L2), tidak didistribusikan ke
> proyek konsumen sebagai template.
