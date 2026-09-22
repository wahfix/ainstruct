# Changelog

Semua perubahan menonjol pada proyek ini dicatat di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/), dan versi
mengikuti [Semantic Versioning](https://semver.org/). Versi diambil dari git tag
(`composer.json` sengaja tidak memuat field `version` — versi ditentukan oleh tag).

## [0.4.0] - 2026-09-22

### Added

- `ainstruct webui` sesi OpenCode: jalankan `opencode run` dari browser, pantau
  output, hentikan proses, lanjutkan sesi via session ID manual, dan hapus
  catatan sesi (endpoint `DELETE` wajib `force: true`). Proses di-spawn
  terdetach (`escapeshellarg`, tanpa shell bebas). WebUI tetap bind
  `127.0.0.1` (tidak berubah); template built-in terproteksi; webui tidak
  menjalankan distribusi. Enam endpoint API v1 terdokumentasi di
  `web/README.md`.
- Streaming output via **SSE** (Server-Sent Events): endpoint
  `GET /api/opencode/sessions/{id}/stream` menyajikan `text/event-stream`.
  Event `output` (chunk output baru), `done` (sesi selesai; klien tutup
  koneksi), `timeout` (idle 5 menit), `error`. Frontend memakai `EventSource`
  sehingga output tampil real-time tanpa delay polling; daftar sesi tetap
  di-polling 3 detik.
- Environment variable: `AINSTRUCT_OPENCODE_BIN` untuk path binari opencode,
  `AINSTRUCT_STATE_HOME` / `XDG_STATE_HOME` untuk state directory sesi webui.

### Tests

- `tests/Feature/Web/OpencodeApiTest.php`: 14 test, 62 assertion — status,
  start, stop, delete, list, validasi input, origin guard, dan stream (SSE
  output + done event + header). Fixture `tests/Fixtures/bin/fake-opencode`
  (spawn tanpa model asli).

### Catatan

- Rilis ini mencakup PR #60 (sesi opencode di WebUI) dan PR #61 (streaming
  output via SSE). Branch protection `main` kini mewajibkan 13 status checks
  (termasuk GitGuardian Security Checks).
- Terverifikasi: health-check 128 pass, phpunit 121/121 (475 assertion),
  pint/phpstan lokal hijau, CI Lint / Meta / Tests / WebUI smoke test sukses
  di `main`.

## [0.3.0] - 2026-09-21

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

### Fixed

- `template create --from ~/repo` dan sumber git bertanda tilde kini berhasil
  diimpor: tilde diekspansi ke direktori home sebelum `git clone` (sebelumnya
  `escapeshellarg` memblokir ekspansi, sumber `~/...` tak pernah bisa dipakai).
- `template clone` tidak lagi mewarisi `ainstruct.source` dari template sumber.
  Klon adalah salinan independen; `template update` pada klon tidak akan menarik
  ulang dari repo asal sumber.

### CI

- Workflow Tests kini menjalankan phpunit dan smoke test WebUI (`php -S` + API +
  tipe MIME aset statis) sehingga regresi router ikut terdeteksi di CI.

### Catatan

- Rilis ini mencakup PR #57 (WebUI + impor git) dan PR #58 (perbaikan tilde,
  clone, penguatan CI). Branch protection `main` kini mewajibkan 12 status
  checks (termasuk PHPUnit dan WebUI smoke test).
- Terverifikasi: health-check 128 pass, pint/phpstan/phpunit lokal hijau,
  CI Lint / Meta / Tests sukses di `main`.

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
[0.3.0]: https://github.com/wahfix/ainstruct/releases/tag/v0.3.0
