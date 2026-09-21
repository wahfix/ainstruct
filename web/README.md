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

## Keamanan

- Tidak pernah menjalankan distribusi; command ini aman dari direktori mana pun.
- Template built-in tidak bisa dihapus, diupdate, atau ditulis lewat API.
- Operasi destruktif (update/delete) butuh `force: true` di body permintaan.
- Path file dibatasi di dalam direktori template; traversal dan symlink keluar
  ditolak. File biner dan file di atas 2 MB tidak bisa dibaca/ditulis.
- Permintaan mutasi dari origin selain localhost (127.0.0.1, localhost, ::1)
  ditolak guard CSRF lokal. Jangan expose server ini ke jaringan publik.

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

Kode status: 200 sukses, 201 dibuat (create), 403 terproteksi atau asal
dilarang, 404 template atau rute tidak ditemukan, 405 metode tidak diizinkan,
409 konflik/opsi tidak sah, 422 validasi, 500 kegagalan lain.

## Susunan file

- `index.php` router untuk `php -S` (API + shell SPA)
- `index.html` kerangka halaman dan dialog
- `assets/styles.css` gaya (tema gelap terminal, aksen cyan)
- `assets/app.js` logika antarmuka (vanilla JS, tanpa build step)
- `assets/logo.svg` salinan logo paket
