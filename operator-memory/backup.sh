#!/usr/bin/env bash
# operator-persona — backup.sh (sinkronisasi DUA ARAH)
# 1. Tarik perubahan dari GitHub (perangkat lain menghasilkan data)
# 2. Gabungkan dengan konfigurasi live lokal (3-way merge bila keduanya berubah)
# 3. Commit + push perubahan gabungan ke GitHub
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CFG_BASE="${XDG_CONFIG_HOME:-$HOME/.config}"
OPENCODE_DIR="$CFG_BASE/opencode"

# File yang disinkronkan (relatif terhadap config/)
# memory.md legacy TIDAK lagi disinkronkan — sudah dimigrasi ke persona.md
# (siapa operator, stabil) + context.md (di mana kita, dinamis) + behavior-log.md
# (catatan lengkap SEMUA perilaku) di bootstrap.
FILES=(
  "opencode.jsonc"
  "skills/operator-memory/SKILL.md"
  "skills/operator-memory/persona.md"
  "skills/operator-memory/context.md"
  "skills/operator-memory/behavior-log.md"
)

cd "$REPO_DIR"

log() { printf '[operator-persona] %s\n' "$*"; }

# Branch aktif (dinamis — GitHub pakai main; clone kosong bisa master)
BRANCH="$(git symbolic-ref -q --short HEAD || echo main)"

# -- 1. TARIK perubahan dari GitHub --
BASE_HEAD="$(git rev-parse -q --verify HEAD || true)"
log "menarik perubahan dari GitHub (branch $BRANCH)..."
if git remote | grep -q '^origin$'; then
  if ! git fetch origin 2>&1; then
    log "FETCH GAGAL — jaringan/otentikasi bermasalah, hentikan"; exit 1
  fi
  if git rev-parse -q --verify "origin/$BRANCH" >/dev/null 2>&1; then
    if ! git merge --ff-only "origin/$BRANCH" 2>&1; then
      log "MERGE GAGAL — ada komit lokal belum di-push dan remote maju;" \
          "tangani manual lalu jalankan ulang."; exit 1
    fi
    log "remote sinkron (HEAD $(git log -1 --format=%h))"
  else
    log "remote belum memiliki branch $BRANCH — lewati tarik"
  fi
else
  log "remote origin tidak ada — lewati tarik"
fi

# Isi file di BASE_HEAD (keadaan terakhir perangkat ini sebelum tarik) → stdout.
base_content() {
  local f="$1"
  if [ -n "$BASE_HEAD" ] && git cat-file -e "$BASE_HEAD:config/$f" 2>/dev/null; then
    git show "$BASE_HEAD:config/$f"
  fi
}

changed=0

# -- 2. SYNC tiap file (live ↔ repo, merge 3-arah bila perlu) --
for f in "${FILES[@]}"; do
  live_f="$OPENCODE_DIR/$f"
  repo_f="$REPO_DIR/config/$f"

  [ -f "$live_f" ] || [ -f "$repo_f" ] || continue

  # Versi base (keadaan terakhir di perangkat ini sebelum sync)
  base_tmp="$(mktemp)"
  base_content "$f" > "$base_tmp" 2>/dev/null || true

  if [ -f "$live_f" ] && [ -f "$repo_f" ]; then
    if cmp -s "$live_f" "$repo_f"; then
      rm -f "$base_tmp"; continue   # sudah sama
    fi

    if [ -f "$base_tmp" ] && [ -s "$base_tmp" ] && cmp -s "$repo_f" "$base_tmp"; then
      # remote tidak menyentuh file → hanya local yang berubah → dorong local
      cp "$live_f" "$repo_f"
      log "local update: $f"; changed=1

    elif [ -f "$base_tmp" ] && [ -s "$base_tmp" ] && cmp -s "$live_f" "$base_tmp"; then
      # local tidak berubah → hanya remote → ADOPSI remote ke live lokal
      cp "$repo_f" "$live_f"
      log "adopsi remote (perangkat lain): $f"; changed=1

    else
      # KEDUA sisi berubah → merge 3-arah manual (base + local + remote)
      if [ -s "$base_tmp" ]; then
        merged_tmp="$(mktemp)"
        git merge-file -p "$live_f" "$base_tmp" "$repo_f" > "$merged_tmp" 2>/dev/null || true
        if grep -qE '^<<<<<<<|^=======$|^>>>>>>>' "$merged_tmp"; then
          log "KONFLIK $f — gabung aman (lokal + blok remote); mohon dirapikan oleh operator"
          cp "$live_f" "$merged_tmp"
          {
            printf '\n--- SINKRONISASI PERANGKAT LAIN (%s) — PERLU DIRAPIKAN MANUAL ---\n' "$(date -u '+%Y-%m-%d %H:%M UTC')"
            printf '=== Konten remote (perangkat lain) mulai di bawah ini ===\n'
            cat "$repo_f"
          } >> "$merged_tmp"
        fi
        cp "$merged_tmp" "$live_f"
        cp "$merged_tmp" "$repo_f"
        rm -f "$merged_tmp"
      else
        # Tidak ada base sama sekali (file baru di kedua sisi) → union sederhana
        log "KONFLIK TANPA BASE $f — gabung berurutan (remote lalu local); mohon dirapikan"
        union_tmp="$(mktemp)"
        cp "$repo_f" "$union_tmp"
        {
          printf '\n--- GABUNGAN DARI PERANGKAT LAIN (%s) — PERLU DIRAPIKAN MANUAL ---\n' "$(date -u '+%Y-%m-%d %H:%M UTC')"
          cat "$live_f"
        } >> "$union_tmp"
        cp "$union_tmp" "$live_f"
        cp "$union_tmp" "$repo_f"
        rm -f "$union_tmp"
      fi
      changed=1
    fi
  elif [ -f "$live_f" ] && [ ! -f "$repo_f" ]; then
    # file hanya ada di live → dorong ke repo
    mkdir -p "$(dirname "$repo_f")"
    cp "$live_f" "$repo_f"
    log "file baru (lokal): $f"; changed=1
  elif [ ! -f "$live_f" ] && [ -f "$repo_f" ]; then
    # file hanya ada di repo (remote) → adopsi ke live
    mkdir -p "$(dirname "$live_f")"
    cp "$repo_f" "$live_f"
    log "file baru (remote): $f"; changed=1
  fi
  rm -f "$base_tmp"
done

# -- 3. COMMIT & PUSH perubahan gabungan --
if [ "$changed" -eq 1 ]; then
  git add -A
  if ! git diff --cached --quiet; then
    git commit -m "Sync $(date -u '+%Y-%m-%d %H:%M UTC')"
    if git remote | grep -q '^origin$'; then
      git push origin "$BRANCH"
      log "sinkron ter-push ke GitHub"
    else
      log "remote origin tidak ada — perubahan tersimpan lokal (local-only)"
    fi
  else
    log "tidak ada perubahan setelah sinkronisasi"
  fi
else
  log "semua file sudah sinkron; tidak ada yang berubah"
fi