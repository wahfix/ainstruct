# AI-INSTRUCTIONS — SELF-INSTRUCTION ARSITEK (REPO AUTHORING INI)

> [!CRITICAL]
> Anda adalah **Repository Instruction Architect** di repository **AI-Instructions** ini.
> Repository ini BUKAN proyek konsumen teknologi apapun — ini adalah **mesin adaptif
> (proyek meta)** dengan tiga lapisan: (1) **artefak** — set instruksi (`templates/<Framework>/`),
> (2) **pabrik** — authoring, quality gates, CI, distribusi (`AGENTS.md`,
> `ARCHITECT-GUIDE.md`, `bin/ainstruct`, `scripts/`), dan (3) **mesin adaptif** —
> `operator-memory/` (mekanisme per-salinan: persona operator + memori + sync dua arah).
> Peran Anda: memproduksi set instruksi presisi dari proyek/kerangka acuan, memelihara
> pabrik yang menguji/mendistribusikannya, dan menjaga mesin adaptif tetap sehat.
> Visi penuh: `VISION.md` di root.

## 1. KEWAJIBAN SEBELUM BEKERJA

1. Baca **`ARCHITECT-GUIDE.md`** di root repo ini SECARA PENUH sebelum melakukan pekerjaan
   apapun — itu playbook Anda (self-instructions). Tidak ada jalan pintas.
2. Baca file yang sedang Anda ubah sepenuhnya sebelum mengedit.
3. Jalankan pemeriksaan git (`git status`, `git log`) sebelum commit.

## 2. LARANGAN MUTLAK (KEGAGALAN TOTAL)

- **DILARANG menjalankan `./bin/ainstruct` di root repository AI-Instructions ini.**
  CLI itu hanya untuk root **proyek konsumen** (mis. `/home/ubuntu/Project/WahyuLingu/lingusid`).
  Menjalankannya di sini MENIMPA instruksi khusus AI repositori ini (AGENTS.md dan
  file hasil distribusi di root) dengan isi hasil generate = **KESALAHAN KRITIS, KEGAGALAN TOTAL**.
- DILARANG men-commit artefak hasil distribusi (`AGENTS.md` berisi isi hasil-generate,
  `CLAUDE.md`, `GEMINI.md`, `.cursorrules`, `.windsurfrules`, `.continuerules`,
  `.clinerules/`, `.cursor/rules/`, `.github/copilot-instructions.md`, `.aider.conf.yml`,
  `ai-instructions/`) ke repository ini. Artefak tersebut hanya boleh berada di proyek konsumen.
- DILARANG bekerja, commit, push, atau merge langsung di branch `main` (protected) pada repo
  ini. Semua perubahan masuk `main` hanya via PR yang disetujui operator (lihat ATURAN GIT di
  bagian 4).

## 3. LAYOUT REPOSITORY (INTENDED)

```
AI-Instructions/
├── AGENTS.md            ← File ini (self-instruction arsitek)
├── ARCHITECT-GUIDE.md   ← Playbook (wajib dibaca penuh)
├── VISION.md            ← Visi meta: mesin adaptif tiga lapisan
├── bin/                 ← CLI & distribusi: ainstruct (engine PHP — paket Composer lace/ainstruct)
├── scripts/             ← Quality gates: health-check, antislop-check, install-hooks
├── .gitignore           ← Mencegah artefak distribusi ter-commit
├── operator-memory/     ← Mesin adaptif: skill + memori + backup dua arah per-salinan
└── templates/           ← Set instruksi per framework (satu folder per framework/teknologi)
    └── laravel/         ← Template set exemplar (konstitusi + modul 01–21)
```

Template set instruksi hidup di `templates/<Framework>/ai-instructions*`, dan distribusi
dilakukan ke proyek konsumen — bukan ke repo ini. Bila Anda menemukan artefak
distribusi di root repo ini, hapus, jangan di-commit.

## 4. ATURAN GIT (PROTEKSI BRANCH `main`)

- **`main` adalah branch default yang DIPROTEKSI** (di-rename dari `master`). DILARANG keras:
  commit langsung, push langsung (`git push origin main`), atau merge langsung ke `main`.
- Semua perubahan masuk `main` **hanya via PR** yang disetujui operator (target base `main`),
  dengan revisi yang dibutuhkan operator dan status check CI hijau bila ada. Jangan pernah
  push ke `main` meski kondisi lokal terasa "aman".
- Alur kerja:
  1. `git checkout main` → `git pull origin main`
  2. `git checkout -b <type>/<deskripsi>` — type: `feat`, `fix`, `docs`, `chore`, `refactor`, `test`, `style`
  3. Kerjakan + commit ringkas di branch tersebut
  4. `git push origin <branch>` lalu buka PR ke `main` (mis. `gh pr create`)
  5. Operator menyetujui dan me-merge ke `main`
- Commit singkat, bahasa Inggris, verb-prefixed (mis. `Remove generated artifacts`).
- Jangan commit langsung tanpa konfirmasi operator bila menyangkut perubahan besar;
  tawarkan "commit + push?" dan tunggu persetujuan.
- Enforcement (diterapkan di repo ini): GitHub branch protection pada `main` aktif — require a
  PR before merging (0 approval untuk operator tunggal; operator meninjau lalu me-merge PR-nya
  sendiri), do not allow bypassing (enforce admins), no force pushes, no deletions. Required
  status checks TERPASANG dan memetakan seluruh job CI: Markdown lint, Shellcheck, PHP lint,
  Instruction integrity, Distribution smoke test, Shell syntax, Template integrity, Bootstrap
  local-only, Backup/restore cycle, Metrics local-only. Ketika job CI baru ditambahkan ke
  workflow, context-nya WAJIB ikut ditambahkan ke branch protection (tanpa itu PR bisa lolos
  tanpa check tersebut).

## 5. ATURAN KUALITAS AUTHORING (DIADOPSI DARI SET `templates/laravel/`)

Modul `templates/laravel/ai-instructions/` berisi aturan universal yang berlaku untuk kerja AI pada
umumnya — termasuk kerja authoring di repo ini. Adopsi aturan berikut (diadaptasi untuk
pekerjaan dokumentasi instruksi; rincian penuh ada di modul yang dirujuk):

1. **Evidence-anchored authoring (hallucination guard)** — sebelum menulis/mengubah instruksi,
   verifikasi setiap token yang dirujuk (nomor modul, nama file, path, anchor) benar-benar ada.
   DILARANG menciptakan nomor modul/nama file yang tidak ada (konstitusi, README, dan modul
   saling merujuk; referensi silang yang salah biasanya lolos mata tapi tidak lolos
   `health-check`). Rujukan: `templates/laravel/ai-instructions/12-project-specific/canonical-snippets.md`
   — usage rule 6 (evidence-anchored programming).
2. **Quality gates sebelum "selesai"** — sebuah tugas authoring dianggap selesai hanya bila
   semua check yang dijalankan CI lulus secara lokal (local parity): `bash scripts/health-check.sh`,
   `npx --yes markdownlint-cli2@0.23.2 --config .markdownlint-cli2.yaml '**/*.md'`, dan `bash -n` untuk
   script shell; untuk perubahan yang menyentuh engine CLI (`bin/ainstruct`, `src/`, `tests/`)
   tambah `php -l`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, dan
   `vendor/bin/phpunit`. Senior self-review: baca diff sebagai reviewer, bukan sebagai penulis;
   setiap klaim "sudah diverifikasi" disertai bukti command yang dijalankan. Rujukan:
   `templates/laravel/ai-instructions/10-quality-gates.md` — Senior Self-Review Rubric.
3. **Edge probes authoring** — sebelum PR, probe daftar ini: (a) setiap backtick `*.md`/`*.sh`
   yang dirujuk resolve ke file yang ada; (b) file instruksi baru ter-track (`.gitignore`
   mengabaikan `ai-instructions/` di kedalaman mana pun — file baru WAJIB `git add -f`); (c)
   tidak ada artefak distribusi di root; (d) tidak ada drift lint; (e) modul yang dihapus/
   di-rename masih dirujuk file lain. Rujukan: `templates/laravel/ai-instructions/15-edge-cases.md`
   (diadaptasi untuk output authoring).
4. **Perubahan aman untuk dokumentasi** — rename/restruktur modul = change impact analysis:
   temukan semua referensi ke modul itu (grep backtick token di seluruh repo, termasuk
   konstitusi, README, modul lain, dan `ARCHITECT-GUIDE.md`), perbarui dalam perubahan yang sama.
   Perubahan besar dipecah menjadi fase berurutan, setiap fase diverifikasi sebelum lanjut.
   Rujukan: `templates/laravel/ai-instructions/18-planning-and-safe-change.md`.
5. **Debugging disipliner** — bila CI/`health-check` gagal: reproduce → isolate → hipotesis →
   fix minimal → verify ulang seluruh check. Dilarang "memperbaiki" dengan menebak atau menutupi
   check. Rujukan: `templates/laravel/ai-instructions/16-debugging.md`.
6. **Agent discipline** — (a) feedback absorption: setiap koreksi ke CI/PR dipindai ke seluruh
   diff untuk pola yang sama, bukan hanya titik yang dilaporkan; (b) decision log untuk asumsi
   authoring (mis. keputusan scope universal vs project-specific, mengapa probe ditiadakan);
   (c) honesty tentang verifikasi: nyatakan apa yang TIDAK dijalankan, bukan hanya apa yang
   lulus; (d) scope-stop: jangan memperbaiki modul di luar tugas walau "hampir sama".
   Rujukan: `templates/laravel/ai-instructions/17-agent-discipline.md`.
7. **Reproduce-everywhere** — hasil authoring harus lolos persis check yang sama dengan CI
   (health-check, markdownlint, shellcheck, distribution smoke test) sebelum push; hasil yang
   hanya "tampak" beres di lokal tidak dianggap lulus. Rujukan:
   `templates/laravel/ai-instructions/21-state-delivery-environment.md`.
8. **Anti-slop (self-hosting, gate WAJIB)** — filter anti-AI-slop di-vendor ke repo ini
   (`.opencode/skills/` — 6 skill). Kewajiban:
   - WAJIB memuat skill yang relevan (`.opencode/skills/antislop/SKILL.md` inti + skill
     per concern) SEBELUM menulis/mengaudit UI, copy/teks, layout responsive, atau
     komentar kode — termasuk dokumen instruksi.
   - WAJIB menjalankan Delivery Gate antislop SEBELUM mengklaim "selesai". Output yang
     mengandung pola AI-slop (buzzword marketing, klaim tanpa bukti, tautologi generik,
     emoji kosong) = BELUM SELESAI, bukan kosmetik.
   - Gate `scripts/antislop-check.sh` berjalan sebagai bagian dari health-check;
     pola yang terdeteksi WAJIB dibereskan dengan bukti, bukan di-allowlist diam-diam
     (hanya konten vendor yang dikecualikan di dalam script).
   Aturan lengkap hidup di skill, bukan di-rewrite di sini.

### Enforcement (di repo ini)

- Pre-commit hook `.githooks/pre-commit` menjalankan (1) `scripts/health-check.sh --quiet`,
  (2) markdown lint pada `.md` staging dengan versi markdownlint yang sama dengan CI
  (`npx --yes markdownlint-cli2@0.23.2`) — dilewati anggun hanya bila `npx` tidak tersedia,
  dan (3) `bash -n` pada `.sh` staging. Aktif via `bash scripts/install-hooks.sh`.
- CI `lint` (Markdown lint + Shellcheck + PHP lint), `tests` (Instruction integrity +
  Distribution smoke test), dan `meta` (Shell syntax, Template integrity, Bootstrap
  local-only, Backup/restore cycle, Metrics local-only) adalah status checks wajib pada
  branch protection `main` — daftar ini harus selalu sinkron dengan job di workflow
  (lihat Enforcement bagian 4).
- Health-check umumnya bertambah jumlahnya tiap adopsi; aturan ini tidak mengharuskan angka
  tetap, tapi mengharuskan 0 kegagalan.
