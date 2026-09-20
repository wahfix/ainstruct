# AI-Instructions — Mesin Adaptif (Proyek Meta)

> Repositori ini bukan sekadar kumpulan template instruksi.
> Ini adalah **mesin adaptif**: sistem yang **memproduksi**, **menguji**,
> **mendistribusikan**, lalu **mengadaptasi** set instruksi AI — dan setiap
> salinannya membawa mekanisme yang sama dengan operator miliknya sendiri.

## Tiga Lapisan

```
LAPISAN 1 — ARTEFAK (yang dikonsumsi proyek konsumen)
└── templates/laravel/   set instruksi berakar pada LingSID (konstitusi + modul 01–21)

LAPISAN 2 — PABRIK (yang memproduksi & menjaga lapisan 1)
├── AGENTS.md        self-instruction arsitek (peran, larangan, aturan git)
├── ARCHITECT-GUIDE.md   playbook authoring (wajib dibaca penuh)
├── bin/             ainstruct (engine PHP — paket Composer lace/ainstruct) (HANYA di proyek konsumen)
├── scripts/         health-check, install-hooks
└── .github/workflows/  CI: markdownlint, shellcheck, integrity, smoke test, meta

LAPISAN 3 — MESIN ADAPTIF (yang membuat setiap salinan menjadi milik operatornya)
└── operator-memory/  skill + memori per-operator + backup/restore dua arah
```

- **Lapisan 1 + 2** membentuk *bengkel authoring klasik*: instruksi dibuat, diuji,
  lalu didistribusikan ke proyek konsumen.
- **Kapabilitas sistem, bukan template**: tim authoring multi-agent dan filter anti-AI-slop
  hidup di lapisan 2 (protokol internal + gate) — tidak terdistribusi sebagai set instruksi.
- **Lapisan 3** adalah lompatan meta: setiap **salinan repo ini** membawa mekanisme
  yang sama (`operator-memory/`), dan operator salinan tersebut menjalankan
`operator-memory/bootstrap-operator-memory.sh` untuk membuat **persona miliknya
   sendiri** — identitas, gaya, pola keputusan — disimpan di `~/.config/opencode/skills/operator-memory/persona.md` + `~/.config/opencode/skills/operator-memory/context.md` dan di-sinkronkan
   **dua arah** ke repo privat GitHub operator.

## Prinsip Self-Hosting

Aturan yang repo ini tulis untuk AI pada umumnya **berlaku untuk kerja repo ini
sendiri**: evidence-anchored authoring, quality gates sebelum "selesai", edge
probes, change impact analysis, debugging disipliner, agent discipline, dan
reproduce-everywhere (diadopsi dari `templates/laravel/ai-instructions/` ke `AGENTS.md` §5).

Artinya: **mesin ini memproduksi instruksi menggunakan standar yang sama dengan
instruksi yang diproduksinya.** Tidak ada dua standar — untuk konsumen dan untuk
diri sendiri.

## Siklus Hidup Sebuah Instruksi

```
DIPRODUKSI → DIUJI → DIDISTRIBUSIKAN → DIADAPTASI → DIINGAT
   authoring    health-check       bin/ainstruct         master/ custom   operator-memory
                + CI               (proyek konsumen)     per proyek      (per salinan, per operator)
```

1. **Diproduksi** — set instruksi dibuat/diperbarui di `templates/<Framework>/` (L1) memakai
   playbook `ARCHITECT-GUIDE.md` (L2).
2. **Diuji** — setiap perubahan harus lolos `scripts/health-check.sh`, markdownlint,
   shellcheck, dan smoke test distribusi (L2; CI di `.github/workflows/`).
3. **Didistribusikan** — `bin/ainstruct` (CLI Composer `lace/ainstruct`)
   menebar set ke proyek konsumen; konsumen bisa men-custom via
   `ai-instructions/master/` atau `template` miliknya.
4. **Diadaptasi** — setiap fondasi itu hidup di banyak lingkungan; nilai akhirnya
   ditentukan oleh kontribusi & spesialisasi proyek.
5. **Diingat** — `operator-memory/` (L3) membuat setiap salinan repo **belajar dari
   operatornya**: keputusan, koreksi, pola. Memori tidak hilang saat pindah mesin
   (restore) dan tidak menimpa antar perangkat (sync dua arah).

## Konsekuensi Praktis

- **Arah pengembangan utama repo ini = meta**: memperbaiki mesin (L2/L3) sama
  pentingnya dengan memperbaiki set instruksi (L1).
- **Setiap salinan adalah entitas yang sama tapi personal**: mekanisme identik di
  semua salinan; memori & kepribadian berbeda per operator. Tidak ada data operator
  yang saling menimpa — `operator-memory/backup.sh` selalu menarik lalu menggabungkan.
- **Batasan tetap berlaku**: repo ini BUKAN proyek konsumen; artefak distribusi
  tidak pernah di-commit ke sini; `main` dilindungi (lewat PR); `bin/ainstruct`
  hanya dijalankan di root proyek konsumen.
- **Kejujuran skala**: lapisan meta adalah investasi — nilainya terukur dari
  seberapa presisi instruksi yang diproduksi dan seberapa mulus adaptasi per operator.
