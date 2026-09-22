# WebUI Template

Antarmuka browser untuk mengelola template AI Instructions, bagian dari paket
`lace/ainstruct`. Dijalankan lewat command `ainstruct webui` (server PHP
bawaan, tanpa runtime dependency tambahan).

## Menjalankan

```bash
ainstruct webui
```

Server bind ke `127.0.0.1:8787` secara default dan membuka browser otomatis.
Opsi: `--host <ip>`, `--port <port>`, `--no-open` (lihat `ainstruct help webui`).

## Cakupan

- Daftar template: built-in (terproteksi) dan custom milik konsumen.
- Kelola custom: create (scaffold atau impor git), clone, update, delete.
- Penjelajah dan editor file template; built-in hanya bisa dibaca.
- Informasi `ainstruct.source` untuk template yang pernah diimpor dari git.
- **Sesi opencode**: jalankan `opencode run` di direktori proyek pilihan
  (default: direktori tempat `ainstruct webui` dijalankan), pantau output real-time
  via SSE (Server-Sent Events; server `php -S` single-thread sehingga proses
  di-spawn terdetach), hentikan proses, lanjutkan sesi lama via session ID,
  dan hapus catatan sesi.

## Keamanan

- Tidak pernah menjalankan distribusi; command ini aman dari direktori mana pun.
- Template built-in tidak bisa dihapus, diupdate, atau ditulis lewat API.
- Operasi destruktif (update/delete) butuh `force: true` di body permintaan.
- Path file dibatasi di dalam direktori template; traversal dan symlink keluar
  ditolak. File biner dan file di atas 2 MB tidak bisa dibaca/ditulis.
- Permintaan mutasi dari origin selain localhost (127.0.0.1, localhost, ::1)
  ditolak guard CSRF lokal. Jangan expose server ini ke jaringan publik.
- **Sesi opencode menjalankan perintah eksternal** di direktori yang dipilih
  pengguna. Restriksi: satu-satunya perintah yang dijalankan adalah
  `opencode run` dengan argumen yang di-escape penuh — tidak ada lintasan
  perintah shell dari input bebas. `--auto` hanya dikirim bila dicentang
  pengguna (mengizinkan agent mengubah file tanpa konfirmasi). Jalankan server
  hanya pada `127.0.0.1`; jangan expose ke jaringan publik.
- Menjalankan sesi hanya didukung di POSIX (Linux/macOS/BSD/Android); di
  Windows endpoint mulai sesi mengembalikan 409.
- Catatan sesi disimpan di state dir WebUI
  (`AINSTRUCT_STATE_HOME` → `XDG_STATE_HOME` → `HOME/.local/state`,
  lalu `ainstruct/webui/opencode/`). Menghapus sesi permanen butuh
  `force: true`; proses yang masih berjalan ikut dihentikan.

## API

Semua respons JSON dengan bentuk `{ok:true,data}` atau `{ok:false,error}`.

| Metode | Path | Fungsi |
| --- | --- | --- |
| GET | `/api/templates` | Daftar template (builtin + custom) |
| POST | `/api/templates` | Buat (scaffold) atau impor dari sumber git |
| GET | `/api/templates/{name}` | Detail satu template |
| POST | `/api/templates/{name}/clone` | Clone dari template sumber |
| PUT | `/api/templates/{name}` | Update custom dari sumber (butuh force) |
| DELETE | `/api/templates/{name}` | Hapus custom (butuh force) |
| GET | `/api/templates/{name}/tree?path=` | Isi direktori template |
| GET | `/api/templates/{name}/file?path=` | Baca file teks |
| PUT | `/api/templates/{name}/file` | Tulis file (custom saja) |
| GET | `/api/opencode/status` | Ketersediaan binary opencode + versi + direktori default |
| GET | `/api/opencode/sessions` | Daftar sesi yang dijalankan dari WebUI (terbaru dulu) |
| POST | `/api/opencode/sessions` | Mulai sesi `opencode run` (direktori + prompt) |
| GET | `/api/opencode/sessions/{id}` | Meta + status + output sesi (tail dibatasi) |
| GET | `/api/opencode/sessions/{id}/stream` | SSE streaming output sesi (real-time) |
| POST | `/api/opencode/sessions/{id}/stop` | Hentikan proses sesi |
| DELETE | `/api/opencode/sessions/{id}` | Hapus catatan + log sesi (butuh force) |

Body `POST /api/opencode/sessions`:

| Field | Wajib | Keterangan |
| --- | --- | --- |
| `directory` | ya | Direktori proyek (harus sudah ada; realpath) |
| `prompt` | ya | Instruksi untuk opencode (maks 10.000 karakter) |
| `model` | tidak | Diteruskan sebagai `opencode run --model <nilai>` |
| `agent` | tidak | Diteruskan sebagai `opencode run --agent <nilai>` |
| `session` | tidak | ID sesi opencode untuk lanjut (`--session`) |
| `auto` | tidak | Bool; menambah `--auto` (setujui izin otomatis) |

**SSE Stream** (`GET /api/opencode/sessions/{id}/stream`):

Mengembalikan `text/event-stream`. Event yang dikirim:

| Event | Data | Keterangan |
| --- | --- | --- |
| `output` | `{content: string}` | Chunk output baru dari proses |
| `done` | `{status: string}` | Sesi selesai (`finished` atau `stopped`) |
| `timeout` | `{message: string}` | Sesi timeout (5 menit idle) |
| `error` | `{error: string}` | Error tak terduga |

Klien harus menutup koneksi setelah menerima `done`, `timeout`, atau `error`.
Browser `EventSource` menangani reconnect otomatis bila koneksi terputus sebelum
`done` (misalnya karena `php -S` restart).

Kode status: 200 sukses, 201 dibuat (create/start), 403 terproteksi atau asal
dilarang, 404 template/sesi atau rute tidak ditemukan, 405 metode tidak
diizinkan, 409 konflik/opsi tidak sah (termasuk Windows untuk sesi), 422
validasi, 500 kegagalan lain.

## Susunan file

- `index.php` router untuk `php -S` (API + shell SPA)
- `index.html` kerangka halaman dan dialog
- `assets/styles.css` gaya (tema gelap terminal, aksen cyan)
- `assets/app.js` logika antarmuka (vanilla JS, tanpa build step)
- `assets/logo.svg` salinan logo paket
