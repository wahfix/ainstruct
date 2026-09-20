#!/usr/bin/env bash
#
# bootstrap-operator-memory.sh — instansiasi mekanisme operator-memory
#
# Setiap salinan repository ini (fork/clone/distribusi) membawa mekanisme yang
# sama: skill global + memori operator + backup/sinkronisasi DUA ARAH ke repo
# privat GitHub. Script ini mem-personalisasi mekanisme tersebut untuk
# OPERATOR SALINAN ini — identitas, repo backup, dan memori masing-masing.
#
# Alur:
#   1. Identitas operator (nama, email, handle GitHub) — argumen atau interaktif.
#   2. Buat repo privat GitHub <handle>/<repo> (@gh) bila belum ada; clone ke
#      ${XDG_CONFIG_HOME:-$HOME/.config}/operator-persona.
#   3. Pasang skill + memori starter (persona.md + context.md + behavior-log.md)
#      ke ~/.config/opencode/ (memori lama dijaga; memory.md legacy dimigrasi).
#   4. Tautkan persona.md + context.md ke opencode.jsonc (dibuat bila belum ada).
#   5. Sinkronkan config ke repo backup, commit + push.
#
# Setelah selesai: restart opencode. Agent selanjutnya membaca persona.md (siapa
# operator) + context.md (di mana kita), mencatat SEMUA perilaku operator di
# behavior-log.md, memperbarui memori di checkpoint kerja, dan menjalankan
# backup.sh (dua arah).
# Restore di mesin lain: clone repo privat lalu jalankan restore.sh.
#
# Opsi non-interaktif / CI / pengujian:
#   --name NAME     --email EMAIL     --github HANDLE
#   --repo NAME     (default: operator-persona)
#   --dest DIR      (lokasi salinan repo backup; default ~/.config/operator-persona)
#   --remote-url URL (pakai remote ini alih-alih git@github.com:<handle>/<repo>.git
#                     — untuk pengujian/sandbox)
#   --local-only    (tanpa gh / tanpa remote — repo git lokal saja)
set -euo pipefail

usage() {
  cat <<'EOF'
Penggunaan: bootstrap-operator-memory.sh [opsi]

  --name NAME          Nama operator (prompt bila kosong)
  --email EMAIL        Email operator (prompt bila kosong)
  --github HANDLE      Handle GitHub (prompt bila kosong)
  --repo NAME          Nama repo backup privat (default: operator-persona)
  --dest DIR           Lokasi salinan repo backup (default: ~/.config/operator-persona)
  --remote-url URL     Remote untuk clone/push (untuk sandbox/pengujian)
  --local-only         Lewati gh & remote — repo git lokal saja
  -h, --help           Tampilkan bantuan ini
EOF
}

# --- Parsing argumen ---
NAME=""
EMAIL=""
GITHUB_HANDLE=""
BACKUP_REPO="operator-persona"
DEST=""
REMOTE_URL=""
LOCAL_ONLY=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --name) NAME="${2:-}"; shift 2 ;;
    --email) EMAIL="${2:-}"; shift 2 ;;
    --github) GITHUB_HANDLE="${2:-}"; shift 2 ;;
    --repo) BACKUP_REPO="${2:-}"; shift 2 ;;
    --dest) DEST="${2:-}"; shift 2 ;;
    --remote-url) REMOTE_URL="${2:-}"; shift 2 ;;
    --local-only) LOCAL_ONLY=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "argumen tak dikenal: $1" >&2; usage; exit 1 ;;
  esac
done

TOOLKIT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CFG_BASE="${XDG_CONFIG_HOME:-$HOME/.config}"
OPENCODE_DIR="$CFG_BASE/opencode"
SKILL_DIR="$OPENCODE_DIR/skills/operator-memory"
DEST="${DEST:-$CFG_BASE/operator-persona}"
DATE_UTC="$(date -u '+%Y-%m-%d %H:%M UTC')"

log() { printf '[bootstrap] %s\n' "$*"; }

# --- Identitas operator ---
if [[ -z "$NAME" ]]; then
  read -rp "Nama operator: " NAME
fi
if [[ -z "$EMAIL" ]]; then
  read -rp "Email operator: " EMAIL
fi
if [[ -z "$GITHUB_HANDLE" && $LOCAL_ONLY -eq 0 ]]; then
  read -rp "Handle GitHub (mis. lacevoid): " GITHUB_HANDLE
fi

if [[ -z "$NAME" || -z "$EMAIL" ]]; then
  log "GAGAL: nama dan email wajib diisi." >&2
  exit 1
fi
if [[ $LOCAL_ONLY -eq 0 && -z "$GITHUB_HANDLE" ]]; then
  log "GAGAL: handle GitHub wajib diisi (atau gunakan --local-only)." >&2
  exit 1
fi

log "Identitas: $NAME <$EMAIL>"
log "Repo backup: ${GITHUB_HANDLE:-LOCAL}/$BACKUP_REPO"

# --- Repo GitHub privat (bila diminta) ---
if [[ $LOCAL_ONLY -eq 0 && -z "$REMOTE_URL" ]]; then
  if command -v gh >/dev/null 2>&1; then
    if gh repo view "$GITHUB_HANDLE/$BACKUP_REPO" >/dev/null 2>&1; then
      log "repo GitHub sudah ada: $GITHUB_HANDLE/$BACKUP_REPO (dipakai apa adanya)"
    else
      log "membuat repo privat: $GITHUB_HANDLE/$BACKUP_REPO ..."
      gh repo create "$GITHUB_HANDLE/$BACKUP_REPO" --private || {
        log "GAGAL membuat repo via gh." >&2; exit 1; }
      log "repo privat dibuat."
    fi
  else
    log "PERINGATAN: 'gh' tidak terpasang — repo GitHub tidak dibuat otomatis."
    log "           Buat repo privat https://github.com/new bernama $BACKUP_REPO"
    log "           lalu jalankan ulang dengan --remote-url git@github.com:$GITHUB_HANDLE/$BACKUP_REPO.git"
  fi
fi

REMOTE_URL="${REMOTE_URL:-git@github.com:${GITHUB_HANDLE:-x}/$BACKUP_REPO.git}"

# --- Clone / buat salinan lokal repo backup ---
if [[ -d "$DEST/.git" ]]; then
  log "salinan lokal sudah ada: $DEST"
  if [[ $LOCAL_ONLY -eq 0 ]] && git -C "$DEST" remote | grep -q '^origin$'; then
    ( cd "$DEST" && git pull --ff-only 2>&1 | tail -1 ) || log "PERINGATAN: pull gagal — lanjut dengan state lokal."
  fi
elif [[ -d "$DEST" ]]; then
  log "GAGAL: $DEST ada tapi bukan repo git." >&2
  exit 1
else
  if [[ $LOCAL_ONLY -eq 1 ]]; then
    git init "$DEST" >/dev/null 2>&1
    log "repo git lokal dibuat: $DEST"
  else
    log "clone: $REMOTE_URL -> $DEST"
    git clone "$REMOTE_URL" "$DEST" 2>&1 | tail -2 || {
      log "GAGAL clone — periksa remote/auth." >&2; exit 1; }
  fi
fi

mkdir -p "$SKILL_DIR"
mkdir -p "$DEST/config/skills/operator-memory"

# --- Pasang skill (mekanisme — selalu dari template) ---
if [[ -f "$TOOLKIT_DIR/skill/SKILL.md" ]]; then
  sed -e "s|{{NAME}}|$NAME|g" \
      -e "s|{{EMAIL}}|$EMAIL|g" \
      -e "s|{{GITHUB_HANDLE}}|$GITHUB_HANDLE|g" \
      -e "s|{{BACKUP_REPO}}|$BACKUP_REPO|g" \
      -e "s|{{DATE}}|$DATE_UTC|g" \
      "$TOOLKIT_DIR/skill/SKILL.md" > "$SKILL_DIR/SKILL.md"
  log "skill dipasang: $SKILL_DIR/SKILL.md"
else
  log "GAGAL: template skill tidak ditemukan di $TOOLKIT_DIR/skill/SKILL.md" >&2
  exit 1
fi

# --- Migrasi memory.md legacy → persona.md + context.md (deterministik) ---
# Heading yang termasuk konteks dinamis; sisanya adalah persona (stabil).
CONTEXT_HEADING_PREFIXES=("State saat ini" "Log")
is_context_heading() {
  local h="$1"
  for p in "${CONTEXT_HEADING_PREFIXES[@]}"; do
    if [[ "$h" == "## $p"* ]]; then return 0; fi
  done
  return 1
}

split_legacy_memory() {
  # $1 = sumber memory.md legacy, $2 = direktori tujuan (SKILL_DIR)
  # Hasil: $2/persona.md + $2/context.md. Sumber TIDAK dihapus.
  local src="$1" dst="$2"
  log "migrasi memory.md legacy -> persona.md + context.md"
  local bucket="persona"
  local persona_file="$dst/persona.md"
  local context_file="$dst/context.md"
  : > "$persona_file"
  : > "$context_file"
  while IFS= read -r line || [[ -n "$line" ]]; do
    if [[ "$line" == "## "* ]]; then
      if is_context_heading "$line"; then bucket="context"; else bucket="persona"; fi
    fi
    if [[ "$bucket" == "persona" ]]; then
      printf '%s\n' "$line" >> "$persona_file"
    else
      printf '%s\n' "$line" >> "$context_file"
    fi
  done < "$src"
}

# --- Pasang memori: persona.md (stabil) + context.md (dinamis) ---
PERSONA_REF=""
CONTEXT_REF=""
if [[ -f "$SKILL_DIR/persona.md" && -f "$SKILL_DIR/context.md" ]]; then
  log "memori live dipertahankan: $SKILL_DIR/persona.md + context.md"
elif [[ -f "$SKILL_DIR/memory.md" ]]; then
  # Legacy dari versi awal: migrasi sekali, sumber dijaga utuh.
  split_legacy_memory "$SKILL_DIR/memory.md" "$SKILL_DIR"
  log "memory.md legacy tetap tersimpan (tidak dihapus)."
elif [[ -f "$DEST/config/skills/operator-memory/persona.md" \
     && -f "$DEST/config/skills/operator-memory/context.md" ]]; then
  cp "$DEST/config/skills/operator-memory/persona.md"  "$SKILL_DIR/persona.md"
  cp "$DEST/config/skills/operator-memory/context.md"  "$SKILL_DIR/context.md"
  log "memori diadopsi dari repo backup (perangkat lain)."
elif [[ -f "$DEST/config/skills/operator-memory/memory.md" ]]; then
  cp "$DEST/config/skills/operator-memory/memory.md" "$SKILL_DIR/memory.md"
  split_legacy_memory "$SKILL_DIR/memory.md" "$SKILL_DIR"
  log "memori legacy diadopsi dari repo backup lalu dimigrasi."
else
  sed -e "s|{{NAME}}|$NAME|g" \
      -e "s|{{EMAIL}}|$EMAIL|g" \
      -e "s|{{GITHUB_HANDLE}}|$GITHUB_HANDLE|g" \
      -e "s|{{BACKUP_REPO}}|$BACKUP_REPO|g" \
      -e "s|{{DATE}}|$DATE_UTC|g" \
      "$TOOLKIT_DIR/skill/persona.md.start" > "$SKILL_DIR/persona.md"
  log "persona starter dibuat: $SKILL_DIR/persona.md"
  sed -e "s|{{NAME}}|$NAME|g" \
      -e "s|{{EMAIL}}|$EMAIL|g" \
      -e "s|{{GITHUB_HANDLE}}|$GITHUB_HANDLE|g" \
      -e "s|{{BACKUP_REPO}}|$BACKUP_REPO|g" \
      -e "s|{{DATE}}|$DATE_UTC|g" \
      "$TOOLKIT_DIR/skill/context.md.start" > "$SKILL_DIR/context.md"
  log "context starter dibuat: $SKILL_DIR/context.md"
fi

# --- Pasang behavior-log.md (catatan lengkap SEMUA perilaku) ---
if [[ -f "$SKILL_DIR/behavior-log.md" ]]; then
  log "behavior-log live dipertahankan: $SKILL_DIR/behavior-log.md"
elif [[ -f "$DEST/config/skills/operator-memory/behavior-log.md" ]]; then
  cp "$DEST/config/skills/operator-memory/behavior-log.md" "$SKILL_DIR/behavior-log.md"
  log "behavior-log diadopsi dari repo backup (perangkat lain)."
else
  sed -e "s|{{NAME}}|$NAME|g" \
      -e "s|{{BACKUP_REPO}}|$BACKUP_REPO|g" \
      -e "s|{{DATE}}|$DATE_UTC|g" \
      "$TOOLKIT_DIR/skill/behavior-log.md.start" > "$SKILL_DIR/behavior-log.md"
  log "behavior-log starter dibuat: $SKILL_DIR/behavior-log.md"
fi

# --- Tautkan persona.md + context.md ke opencode.jsonc global ---
CONFIG_FILE="$OPENCODE_DIR/opencode.jsonc"
# Rujukan portabel bila lokasi standar (~/.config/opencode), agar restore di
# mesin lain tetap menunjuk benar; path absolut bila XDG_CONFIG_HOME di-override.
if [[ "$OPENCODE_DIR" == "$HOME/.config/opencode" ]]; then
  # Tilde disengaja (lihat komentar atas) — jangan diganti $HOME.
  # shellcheck disable=SC2088
  PERSONA_REF="~/.config/opencode/skills/operator-memory/persona.md"
  # shellcheck disable=SC2088
  CONTEXT_REF="~/.config/opencode/skills/operator-memory/context.md"
else
  PERSONA_REF="$SKILL_DIR/persona.md"
  CONTEXT_REF="$SKILL_DIR/context.md"
fi
if [[ ! -f "$CONFIG_FILE" ]]; then
  mkdir -p "$OPENCODE_DIR"
  cat > "$CONFIG_FILE" <<'EOF'
{
  "$schema": "https://opencode.ai/config.json",
  "instructions": [
    "__PERSONA_REF__",
    "__CONTEXT_REF__"
  ]
}
EOF
  sed -i "s|__PERSONA_REF__|$PERSONA_REF|; s|__CONTEXT_REF__|$CONTEXT_REF|" "$CONFIG_FILE"
  log "opencode.jsonc dibuat: $CONFIG_FILE"
elif grep -q 'operator-memory/persona\.md' "$CONFIG_FILE" \
     && grep -q 'operator-memory/context\.md' "$CONFIG_FILE"; then
  log "opencode.jsonc sudah memuat referensi persona.md + context.md (tidak diubah)."
else
  if command -v python3 >/dev/null 2>&1 \
     && python3 -c 'import json,sys; json.load(open(sys.argv[1]))' "$CONFIG_FILE" 2>/dev/null; then
    cp "$CONFIG_FILE" "$CONFIG_FILE.bak.$DATE_UTC"
    python3 - "$CONFIG_FILE" "$PERSONA_REF" "$CONTEXT_REF" <<'PYEOF'
import json, sys
path, p_ref, c_ref = sys.argv[1], sys.argv[2], sys.argv[3]
cfg = json.load(open(path))
cfg.setdefault("instructions", [])
# Buang referensi legacy memory.md bila ada, lalu tambahkan pasangan baru.
cfg["instructions"] = [i for i in cfg["instructions"] if "operator-memory/memory.md" not in i]
for ref in (p_ref, c_ref):
    if ref not in cfg["instructions"]:
        cfg["instructions"].append(ref)
with open(path, "w") as f:
    json.dump(cfg, f, indent=2)
    f.write("\n")
PYEOF
    log "opencode.jsonc diperbarui (referensi persona.md + context.md)."
  else
    log "PERINGATAN: opencode.jsonc sudah ada dan bukan JSON murni —"
    log "           tambahkan manual baris ini ke daftar instructions:"
    log "           \"$PERSONA_REF\" dan \"$CONTEXT_REF\""
  fi
fi

# --- Sinkronkan config ke repo backup + commit/push ---
log "menyalin config ke repo backup..."
cp -f "$SKILL_DIR/SKILL.md"   "$DEST/config/skills/operator-memory/SKILL.md"
cp -f "$SKILL_DIR/persona.md" "$DEST/config/skills/operator-memory/persona.md"
cp -f "$SKILL_DIR/context.md" "$DEST/config/skills/operator-memory/context.md"
cp -f "$SKILL_DIR/behavior-log.md" "$DEST/config/skills/operator-memory/behavior-log.md"
# Legacy memory.md di repo backup dihapus dari config (sudah dimigrasi).
rm -f "$DEST/config/skills/operator-memory/memory.md"
cp -f "$CONFIG_FILE"          "$DEST/config/opencode.jsonc" 2>/dev/null || true
cp -f "$TOOLKIT_DIR/backup.sh"  "$DEST/backup.sh"
cp -f "$TOOLKIT_DIR/restore.sh" "$DEST/restore.sh"
cp -f "$TOOLKIT_DIR/metrics.sh" "$DEST/metrics.sh" 2>/dev/null || true
cp -f "$TOOLKIT_DIR/README.md"  "$DEST/README.md" 2>/dev/null || true
chmod +x "$DEST/backup.sh" "$DEST/restore.sh" "$DEST/metrics.sh" 2>/dev/null || true

# identitas git disimpan ke config backup agar restore.sh bisa menerapkannya
# di mesin baru (clone fresh TIDAK membawa user.name/user.email git lokal).
printf '%s\n%s\n' "${NAME:-Operator}" "${EMAIL:-operator@localhost}" \
  > "$DEST/config/git-identity"

# identitas git lokal (repo backup) — tidak mengubah config global
git -C "$DEST" config user.name  "${NAME:-Operator}"
git -C "$DEST" config user.email "${EMAIL:-operator@localhost}"

git -C "$DEST" add -A
if ! git -C "$DEST" diff --cached --quiet; then
  git -C "$DEST" commit -m "Init: operator persona ($NAME, $DATE_UTC)"
  if [[ $LOCAL_ONLY -eq 0 ]]; then
    git -C "$DEST" push origin HEAD 2>&1 | tail -1 \
      && log "repo backup ter-push ke GitHub." \
      || log "PERINGATAN: push gagal — jalankan backup.sh nanti."
  else
    log "mode lokal: tanpa push."
  fi
else
  log "tidak ada perubahan untuk di-commit."
fi

# --- Ringkasan ---
cat <<EOF

══════════════════════════════════════════════════════════
✅ Operator memory siap untuk: $NAME <$EMAIL>
   Skill + persona  : $SKILL_DIR/persona.md
   Skill + context  : $SKILL_DIR/context.md
   Behavior log     : $SKILL_DIR/behavior-log.md (SEMUA perilaku operator)
   Config global    : $CONFIG_FILE
   Repo backup      : $DEST
   Sinkronisasi     : dua arah (tarik → gabung → push) via backup.sh
   Metrik adaptasi  : $DEST/metrics.sh (statistik persona/context/behavior-log)
══════════════════════════════════════════════════════════
Langkah berikut:
   1. RESTART opencode agar skill aktif.
   2. Saat mencatat kegiatan, agent memperbarui persona.md (siapa operator)
      dan context.md (di mana kita) lalu menjalankan backup.sh (otomatis
      di checkpoint kerja).
   3. Mesin lain: clone repo privat lalu restore.sh.
EOF