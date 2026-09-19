# AI INSTRUCTION SYSTEM — CONSTITUTION (Sumber Kebenaran Tunggal)

File ini adalah **konstitusi** dari sistem instruksi untuk semua AI coding agent di proyek ini. Konstitusi ini memastikan arsitektur, workflow, dan standar engineering tetap konsisten.

> [!CRITICAL]
> **PROTOKOL WAJIB — BACA SEBELUM MENULIS KODE**
>
> Anda DILARANG melakukan perubahan kode, migrasi, service, controller, atau UI SEBELUM membaca:
>
> 1. **File ini** — konstitusi: prioritas, scope, workflow, quality gates.
> 2. **`ai-instructions/01-governance.md`** — hierarchy, conflict resolution, rule scope.
> 3. **`ai-instructions/02-agent-workflow.md`** — workflow wajib 10 langkah.
> 4. **Modul proyek yang berlaku di `ai-instructions/12-project-specific/`**.
> 5. **`MASTER_BUILD_SPECIFICATION.md` di root proyek** — spesifikasi build proyek yang detil, presisi, dan lengkap (nama, fitur, database design, konvensi, dependensi, alur bisnis). Jika file ini **tidak ada**, Anda WAJIB BERHENTI, bertanya secara mendetil kepada operator/programmer yang menugaskan, lalu **membuat file ini secara lengkap dan presisi** sebelum menulis kode apapun (lihat bagian 12).
>
> PELANGGARAN = KEGAGALAN TOTAL. TIDAK ADA PENGECUALIAN.

---

## 1. CARA MEMBACA SISTEM INSTRUKSI

Setiap agent WAJIB membaca file dalam urutan berikut:

1. **Konstitusi ini** — memahami hierarki, prioritas, scope, workflow, dan gates.
2. **`ai-instructions/01-governance.md`** — aturan meta dan resolusi konflik.
3. **`ai-instructions/02-agent-workflow.md`** — urutan kerja wajib.
4. **Modul topikal yang relevan** (`03`–`11`, `13`–`21`) sesuai teknologi dan tugas.
5. **Modul project-specific** (`12-project-specific/`) yang cocok dengan repository saat ini.
6. **`MASTER_BUILD_SPECIFICATION.md`** di root proyek — spesifikasi build proyek (bagian 12). Jika tidak ada, buat terlebih dahulu via diskusi mendetil dengan operator.

Jangan hanya membaca README. Jangan menyimpulkan dari nama file. Baca seluruh modul yang relevan sebelum menulis kode.

---

## 2. FILE MAP INSTRUKSI

| File | Topik | Scope |
|------|-------|-------|
| `ai-instructions.md` | **File ini — konstitusi / entry point** | GLOBAL |
| `ai-instructions/01-governance.md` | Priority, conflict resolution, rule scope | GLOBAL |
| `ai-instructions/02-agent-workflow.md` | Workflow wajib, decision trees, checklist fitur | GLOBAL |
| `ai-instructions/03-architecture.md` | Layer architecture, dependency, patterns, decisions | UNIVERSAL + PROJECT |
| `ai-instructions/04-coding-standards.md` | PHP/TypeScript/Vue style, formatting | UNIVERSAL + PROJECT |
| `ai-instructions/05-naming.md` | Naming convention semua artifact | UNIVERSAL + PROJECT |
| `ai-instructions/06-testing.md` | Testing strategy, pola, konvensi | UNIVERSAL + PROJECT |
| `ai-instructions/07-security.md` | Auth, authorization, validation, secrets | UNIVERSAL + PROJECT |
| `ai-instructions/08-git.md` | Branching, commits, version control | UNIVERSAL + PROJECT |
| `ai-instructions/09-tools.md` | Linter, formatter, runtime, static analysis | UNIVERSAL + PROJECT |
| `ai-instructions/10-quality-gates.md` | Quality gates, senior self-review rubric, verifikasi akhir, gate anti-AI-slop untuk output UI/copy/prosa | GLOBAL |
| `ai-instructions/11-forbidden-behavior.md` | Larangan eksplisit (single source of truth untuk prohibitions; termasuk output AI-slop) | GLOBAL |
| `ai-instructions/13-database.md` | Database rules: migration, relationships, factory, seeder, query, transaction, performance lens | UNIVERSAL + PROJECT |
| `ai-instructions/14-frontend.md` | Frontend rules: Vue 3, TypeScript, Inertia, Tailwind, form handling | UNIVERSAL + PROJECT |
| `ai-instructions/15-edge-cases.md` | Edge-case & boundary probes wajib sebelum "done" | GLOBAL |
| `ai-instructions/16-debugging.md` | Systematic debugging loop (reproduce → isolate → hypothesize → fix → verify) | GLOBAL |
| `ai-instructions/17-agent-discipline.md` | Feedback absorption, decision log & assumption surfacing, honesty tentang verifikasi, scope-stop & eskalasi | GLOBAL |
| `ai-instructions/18-planning-and-safe-change.md` | Phase decomposition & incremental verification, change impact analysis, safe refactoring, test-to-break | GLOBAL |
| `ai-instructions/19-data-reliability.md` | Data migration safety, transactions/locking/concurrency, queue/job reliability, media/upload lifecycle | UNIVERSAL + PROJECT |
| `ai-instructions/20-frontend-and-contracts.md` | Frontend↔backend contract discipline, i18n, a11y & form UX, error contract | UNIVERSAL + PROJECT |
| `ai-instructions/21-state-delivery-environment.md` | State machine, reproduce-everywhere, end-to-end demo verification, dependency hygiene & audit | UNIVERSAL + PROJECT |
| `ai-instructions/12-project-specific/lingusid.md` | Aturan invarian LingSID | PROJECT-SPECIFIC |
| `ai-instructions/12-project-specific/canonical-snippets.md` | Bank snippet kanonik verbatim LingSID (signature, abstraksi, gaya) | PROJECT-SPECIFIC |
| `ai-instructions/README.md` | Laporan analisis & deliverable | DOKUMENTASI |

> **Catatan:** `MASTER_BUILD_SPECIFICATION.md` bukan bagian dari set instruksi ini — file tersebut berada di **root repository proyek yang sedang dikerjakan** dan merupakan spesifikasi build proyek. Ia WAJIB dibaca sebelum kode (bagian 12).

---

## 3. PRINSIP ENGINEERING

Prinsip-prinsip berikut berlaku universal:

1. **Business logic tidak pernah di Controller.** Controller = tipis (input → delegasi → response). Pemisahan logika WAJIB.
2. **Data access terisolasi.** Jangan panggil data layer langsung dari lapisan presentasi.
3. **Validasi input WAJIB server-side.** Tidak pernah mempercayai input pengguna tanpa validasi.
4. **Incremental, bukan dump raksasa.** Kerjakan per fase/modul; verifikasi tiap langkah sebelum lanjut.
5. **Scope discipline.** Hanya ubah file yang relevan dengan fitur. Jangan refactor kode yang berfungsi.
6. **Analogue-first.** Sebelum membuat sesuatu, cari implementasi serupa yang sudah ada; ikuti polanya.
7. **Preserve intent, minimalkan perubahan terkait.** Jangan perbaiki bug yang tidak berhubungan.
8. **Audit & jejak.** Mutasi data yang penting tercatat. Jangan menghapus jejak history.
9. **Keamanan dasar.** Tidak ada password plaintext, tidak ada rahasia di git, tidak ada `dd()/dump()` pada kode tercommit.
10. **Disiplin git.** Jangan commit, push, atau merge langsung di `develop`/`main`. `main` hanya menerima perubahan via release PR yang disetujui dan lolos CI (detail `ai-instructions/08-git.md`). Satu fitur satu branch (dari `develop`). Commit message mengikuti conventional commits.
11. **Quality gates.** Static analysis lalu test yang relevan sebelum pekerjaan dianggap selesai.
12. **Build specification first.** Tidak pernah menulis kode sebelum `MASTER_BUILD_SPECIFICATION.md` dibaca; bila tidak ada, buat via diskusi mendetil dengan operator (bagian 12).
13. **Self-explanatory code (MUST).** Setiap baris kode WAJIB terbaca seperti manusia menjelaskan apa yang dilakukannya — nama variabel/method yang bermakna, fungsi kecil satu tanggung jawab, alur linear — sehingga tidak perlu komentar penjelas. Kode yang butuh komentar agar dimengerti HARUS diperbaiki (rename/extract/simplify), bukan dikomentari. Komentar/docblock hanya diizinkan untuk invariant bisnis yang non-obvious, rationale keputusan (`why`), dan anotasi tipe PHPDoc untuk tooling. Komentar yang mengulang isi kode (chit-chat) DILARANG. Detail: `ai-instructions/04-coding-standards.md` → Code Documentation.

---

## 4. PRIORITY SYSTEM

Saat aturan bertentangan, selesaikan dengan urutan ini (tertinggi menang):

```
LEVEL 0  — System / platform constraints (PHP, Laravel, browser)
LEVEL 1  — User explicit instructions (task saat ini)
LEVEL 2  — Project-specific mandatory rules (invarian, MUST)
LEVEL 3  — Global engineering rules (MUST, REQUIRED)
LEVEL 4  — Project conventions (SHOULD)
LEVEL 5  — Preferences (PREFER, RECOMMENDED)
LEVEL 6  — AI defaults (MAY, OPTIONAL)
```

Instruksi eksplisit user mengalahkan semua aturan di bawah LEVEL 1. Document deviation jika menyentuh integritas arsitektur.

---

## 5. RULE SCOPE

| Scope | Makna |
|-------|-------|
| GLOBAL | Berlaku di semua proyek dan semua tugas |
| UNIVERSAL | Berlaku untuk semua proyek yang memakai sistem ini |
| PROJECT-SPECIFIC | Berlaku hanya bila repository cocok dengan kondisi proyek |
| MODULE | Berlaku hanya pada bagian tertentu dari codebase |
| LANGUAGE | Berlaku hanya pada bahasa tertentu |
| FRAMEWORK | Berlaku hanya pada framework tertentu |
| TASK | Berlaku hanya pada tipe tugas tertentu |

Jangan memaksakan aturan project-specific sebagai aturan global. Detail di `ai-instructions/01-governance.md`.

---

## 6. SEMANTIC STRENGTH

| Keyword | Makna |
|---------|-------|
| MUST / REQUIRED / WAJIB | Persyaratan mutlak. Tanpa pengecualian. |
| MUST NOT / DILARANG / FORBIDDEN | Larangan mutlak. Tanpa pengecualian. |
| SHOULD / SHOULD NOT | Rekomendasi kuat; pelanggaran butuh justifikasi. |
| PREFER / RECOMMENDED | Pendekatan yang disukai; alternatif diterima dengan alasan. |
| MAY / OPTIONAL | Diizinkan tapi tidak diwajibkan. |

Jangan eskalasi SHOULD → MUST. Jangan deeskalasi MUST → SHOULD.

---

## 7. RESOLUSI KONFLIK

```
1. Prioritas lebih tinggi menang (LEVEL 0 > LEVEL 6).
2. Aturan lebih spesifik mengalahkan yang lebih umum.
3. Scope lebih sempit mengalahkan scope lebih luas.
4. Aturan eksplisit yang menyebut aturan lain menang.
5. Jika belum tuntas: pilih yang melindungi DATA INTEGRITY > FINANCIAL
   CORRECTNESS > SECURITY > AUDITABILITY > UX > VISUAL POLISH.
6. Jika masih belum tuntas: BERHENTI dan tanya user. Dilarang memilih arbitrer.
```

---

## 8. WORKFLOW WAJIB (Ringkasan)

```
UNDERSTAND → INSPECT → FIND ANALOGUES → PLAN → IMPLEMENT
→ STATIC ANALYSIS → TEST (hanya jika diminta) → DIFF REVIEW
→ STYLE REVIEW → FINALIZE
```

Detail, decision trees, dan checklist fitur: `ai-instructions/02-agent-workflow.md`.

---

## 9. MENGERJAKAN PROYEK BARU (Future Project Onboarding)

Saat bekerja di proyek baru, agent WAJIB:

1. Bila proyek baru belum repository git, WAJIB menjalankan `git init` pada langkah
   pertama — sebelum pekerjaan, branching, atau commit dimulai (Klausa 4; detail
   `ai-instructions/08-git.md`).
2. Load konstitusi ini + `ai-instructions/01-governance.md` + `ai-instructions/02-agent-workflow.md`.
3. Baca `MASTER_BUILD_SPECIFICATION.md` di root proyek; jika tidak ada, buat melalui diskusi mendetil dengan operator (bagian 12).
4. Inspect repository saat ini (`routes`, `app/`, `resources/js/pages/`, `composer.json`, `package.json`, `phpunit.xml`, `phpstan.neon`, `.editorconfig`).
5. Deteksi teknologi proyek (Laravel/Blade vs Laravel/Inertia/Vue; SQLite/MySQL; bun/npm).
6. Deteksi konvensi project-specific di repository (pola direktori, pola penamaan, pola action/repository).
7. Terapkan global rules.
8. Terapkan aturan framework/language yang berlaku (Laravel, PHP, Vue, TypeScript).
9. Terapkan aturan repository-specific (termasuk `12-project-specific/` jika cocok).
10. Resolve conflict sesuai hierarki pada bagian 4.
11. Bekerja mengikuti workflow bagian 8.
12. Validasi terhadap quality gates di bagian 10.

Jangan berasumsi semua proyek berikutnya memakai stack yang sama. Deteksi, jangan tebak.

---

## 10. QUALITY GATES

Pekerjaan dianggap selesai hanya jika:

- [ ] Code mengikuti pola yang ada (punya analogue di repository).
- [ ] Static analysis lulus (level sesuai konfigurasi proyek).
- [ ] Test relevan lulus (hanya bila diminta).
- [ ] Tidak ada file tak-terkait yang diubah.
- [ ] Code style cocok dengan file tetangga.
- [ ] Tidak ada komentar yang ditambahkan tanpa diminta.
- [ ] Setiap baris kode self-explanatory tanpa komentar yang mengulang isi kode.
- [ ] Tidak ada rahasia/data sensitif yang diperkenalkan.
- [ ] Scope terbatas pada fitur yang diminta.
- [ ] Menghormati semua naming convention.
- [ ] Tidak ada `dd()`, `dump()`, `ray()` pada kode tercommit.
- [ ] Senior self-review rubrik dijalankan (`ai-instructions/10-quality-gates.md`) — setiap kondisi ditelusuri kedua cabang; diff dibaca sebagai reviewer, bukan sebagai penulis.
- [ ] Edge-case probes diterapkan (`ai-instructions/15-edge-cases.md`) untuk setiap code path yang disentuh; yang tidak bisa diputuskan, diangkat ke operator.
- [ ] Setiap kelas/method/signature yang dipakai terverifikasi ada di kode nyata (evidence-anchored — `ai-instructions/12-project-specific/canonical-snippets.md` rule 6).
- [ ] Fitur multi-layer dipecah dalam fase berurutan dan tiap fase terverifikasi sebelum lanjut (`ai-instructions/18-planning-and-safe-change.md`).
- [ ] Kontrak frontend↔backend sinkron dalam perubahan yang sama (`ai-instructions/20-frontend-and-contracts.md`).
- [ ] Perubahan ter-reproduksi di lingkungan bersih (lockfile, migration fresh) dan, bila menyentuh alur user, diverifikasi end-to-end (`ai-instructions/21-state-delivery-environment.md`).
- [ ] Keputusan & asumsi tercatat dalam decision log; hal yang TIDAK diverifikasi dinyatakan eksplisit (`ai-instructions/17-agent-discipline.md`).
- [ ] Output UI/copy/prosa bebas pola AI-slop: filter anti-slop (`.opencode/skills/antislop/SKILL.md` + skill concern) dimuat SEBELUM menulis dan Delivery Gate-nya dijalankan; bila filter tidak terpasang, ambil sistem vendor dari repo AI-Instructions (upstream miqdadbadjuber/anti-slop — MIT) atau terapkan aturan Empty AI Vocabulary secara manual dan nyatakan ketiadaan filter di decision log (detail `ai-instructions/10-quality-gates.md`).

Gates tambahan project-specific: lihat `ai-instructions/10-quality-gates.md` dan module proyek.

---

## 11. FINAL VERIFICATION

Sebelum menyelesaikan tugas:

1. Semua file yang dibuat/diubah memang diperlukan.
2. Code mengikuti seluruh konvensi proyek.
3. Static analysis lulus.
4. Tidak ada speculative change.
5. Scope terbatas pada fitur yang diminta.
6. Self-audit terhadap checklist di bagian 10.

---

## 12. MASTER_BUILD_SPECIFICATION — SPESIFIKASI BUILD PROYEK

`MASTER_BUILD_SPECIFICATION.md` di **root repository proyek** adalah **satu-satunya sumber acuan spesifikasi proyek** bagi AI. File ini mendokumentasikan secara **detil, presisi, dan lengkap** semua hal tentang proyek yang dibutuhkan untuk membangun tanpa menebak, termasuk (tidak terbatas pada):

- Nama proyek, domain, dan tujuan.
- Stack teknologi beserta versi.
- Daftar fitur lengkap per modul (existing, planned, dan arsitekturnya).
- Database design: daftar tabel + kolom + tipe + relasi + index + enum nilai.
- Model/entitas dan konvensi penamaannya.
- Alur bisnis, aturan domain, dan batasan.
- Dependensi/package yang diizinkan.
- Konvensi project-specific dan hal lain yang bisa dipikirkan (UI/UX, bahasa konten, testing).

**Aturan WAJIB:**

1. **Baca sebelum kode.** `MASTER_BUILD_SPECIFICATION.md` WAJIB dibaca sebelum menulis kode apapun — bersama konstitusi, governance, workflow, dan module proyek (protokol CRITICAL di atas).
2. **Jika tidak tersedia, BERHENTI dan BUAT.** Jangan menebak. Tanya operator/programmer yang menugaskan secara **mendetil** (fitur, entitas, database design, konvensi, dependensi), lalu buat `MASTER_BUILD_SPECIFICATION.md` yang **lengkap, detil, dan presisi**. Konfirmasi ke operator sebelum file dianggap valid.
3. **Spesifikasi > aturan umum.** Isi file ini berlaku sebagai definisi proyek (LEVEL 2 — project-specific mandatory) dan mengalahkan aturan framework/global/best-practice. Hanya instruksi eksplisit user (LEVEL 1) yang bisa mengalahkannya.
4. **Sinkronkan.** Bila fitur berubah signifikan dan operator memintanya, perbarui file ini. Jangan menghapus/mengganti definisi tanpa konfirmasi.
5. **Referensi lintas.** Ai-instructions ini tidak menggantikan, dan tidak boleh bertentangan dengan, isi `MASTER_BUILD_SPECIFICATION.md`; perbedaan diselesaikan via hierarki prioritas pada bagian 4.

---

## 13. REFERENSI CEPAT

```
ai-instructions.md                     → File ini — konstitusi
ai-instructions/01-governance.md       → Prioritas & resolusi konflik
ai-instructions/02-agent-workflow.md   → Workflow wajib
ai-instructions/03-architecture.md     → Arsitektur & pola
ai-instructions/04-coding-standards.md → Standar kode
ai-instructions/05-naming.md           → Konvensi penamaan
ai-instructions/06-testing.md          → Konvensi testing
ai-instructions/07-security.md         → Keamanan
ai-instructions/08-git.md              → Git / version control
ai-instructions/09-tools.md            → Penggunaan tools
ai-instructions/10-quality-gates.md    → Quality gates
ai-instructions/11-forbidden-behavior.md → Larangan (single source of truth)
ai-instructions/13-database.md         → Database: migration, factory, seeder, query, performance lens
ai-instructions/14-frontend.md         → Frontend: Vue 3, TypeScript, Inertia, Tailwind
ai-instructions/15-edge-cases.md       → Edge-case & boundary probes sebelum "done"
ai-instructions/16-debugging.md        → Systematic debugging loop
ai-instructions/17-agent-discipline.md → Feedback, decision log, honesty, scope-stop
ai-instructions/18-planning-and-safe-change.md → Decomposition, impact analysis, safe refactor, test-to-break
ai-instructions/19-data-reliability.md → Migration safety, transaction, queue, media lifecycle
ai-instructions/20-frontend-and-contracts.md → FE↔BE contract, i18n, a11y, error contract
ai-instructions/21-state-delivery-environment.md → State machine, reproduce, e2e demo, dependency
ai-instructions/12-project-specific/   → Modul project-specific
ai-instructions/12-project-specific/canonical-snippets.md → Snippet kanonik verbatim
MASTER_BUILD_SPECIFICATION.md          → Root proyek — spesifikasi build (WAJIB dibaca)
```

---

> **PERINGATAN TERAKHIR**: Jika Anda AI agent yang membaca file ini, Anda WAJIB membaca
> modul di `ai-instructions/` DAN `MASTER_BUILD_SPECIFICATION.md` (root proyek — buat terlebih
> dahulu jika belum ada) SEBELUM menulis kode apapun. Tidak ada pengecualian.
> Tidak ada jalan pintas. Kepatuhan penuh terhadap konstitusi ini adalah SYARAT ABSOLUT.
