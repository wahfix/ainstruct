# Changelog

Semua perubahan menonjol pada proyek ini dicatat di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/), dan versi
mengikuti [Semantic Versioning](https://semver.org/). Versi diambil dari git tag
(`composer.json` sengaja tidak memuat field `version` — versi ditentukan oleh tag).

## [Unreleased]

- Belum ada perubahan yang menunggu rilis.

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
