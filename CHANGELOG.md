# Changelog

Semua perubahan menonjol pada proyek ini dicatat di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/), dan versi
mengikuti [Semantic Versioning](https://semver.org/). Versi diambil dari git tag
(`composer.json` sengaja tidak memuat field `version` — versi ditentukan oleh tag).

## [Unreleased]

### Added

- `ainstruct webui`: server lokal + antarmuka browser untuk mengelola template
  (list/create/clone/update/delete/path + penjelajah dan editor file). Reuse
  Actions yang sama dengan CLI sehingga proteksi built-in, validasi, dan sumber
  kebenaran identik. PHP bawaan `php -S` tanpa dependency runtime tambahan;
  default bind `127.0.0.1` dengan guard origin lokal; built-in hanya bisa dibaca;
  operasi destruktif butuh konfirmasi `force: true`; path traversal dan symlink
  keluar ditolak. Ringkasan API di `web/README.md`.
- `template create --from <sumber>` dan `template update --from <sumber>` untuk
  mengimpor template dari sumber git (URL `https`/`ssh` atau jalur repo lokal).
  Sumber dicatat ke `ainstruct.source` di dalam template; `template update` tanpa
  `--from` menarik ulang dari sumber tersimpan. Opsi `--ref <branch|tag>` untuk
  mem-pin ref tertentu. Impor memakai `git clone` dan hasilnya salinan tanpa `.git/`.

## [0.2.5] - 2026-09-20

Rilis pertama yang di-tag (tag git `v0.2.5`).

### Added

- Template set **`templates/vanilla-php/`** (PR #53): konstitusi `ai-instructions.md` +
  modul 01–21 + `12-project-specific/` (`template-baseline.md` & `canonical-snippets.md`) +
  README + detektor `ainstruct-detect.txt` — untuk proyek PHP vanilla dengan DI Laravel
  (Illuminate Container), pola Action + RuledActionContract.

### Catatan

- Riwayat sebelum rilis ini tidak di-tag; isi v0.2.4 (tanpa tag) mencakup cleanup dead
  code (PR #52) dan migrasi agent ke layout agents/ V2 (PR #51).
- Terverifikasi: health-check 127 pass, CI Lint / Meta / Tests sukses di `main`.

[0.2.5]: https://github.com/wahfix/ainstruct/releases/tag/v0.2.5
