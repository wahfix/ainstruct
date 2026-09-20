#!/usr/bin/env bash
# operator-memory — metrics.sh (metrik adaptasi memori per salinan)
#
# Menjawab: "seberapa cepat memori salinan ini berkembang?"
#   * persona.md (stabil)      — apakah potret operator bertumbuh (preferensi,
#                                pola, pelajaran, hipotesis).
#   * context.md (dinamis)     — apakah konteks kerja direkam (checkpoint, log).
#   * behavior-log.md (journal) — apakah SEMUA perilaku operator dicatat
#                                 (entri perilaku teramati).
#   * backup repo              — seberapa rajin sinkronisasi dua arah dijalankan.
#
# Metrik bersifat LOKAL (per salinan) — hanya membaca file live operator ini.
# Tidak mengirim data ke mana pun; tidak ada telemetri terpusat.
#
# Output:
#   default  — ringkasan satu layar untuk operator (manusia).
#   --json   — struktur data untuk agent (diparse / dijit-kaji).
#
# Penggunaan:
#   operator-memory/metrics.sh            # ringkasan manusia
#   operator-memory/metrics.sh --json     # data agent
#   operator-memory/metrics.sh --repo-dir DIR   # override lokasi repo backup
#   operator-memory/metrics.sh --live-dir DIR   # override lokasi skill live
#
# Catatan: dijalankan dari salinan toolkit (repo authoring) ATAU dari salinan
# repo backup (~/.config/operator-persona/metrics.sh). Lokasi repo backup
# dideteksi otomatis dari lokasi script; bisa di-override untuk pengujian.
set -euo pipefail

# --- Deteksi lokasi ---
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CFG_BASE="${XDG_CONFIG_HOME:-$HOME/.config}"
REPO_DIR=""
LIVE_DIR="$CFG_BASE/opencode/skills/operator-memory"
JSON=0

# Jika SCRIPT_DIR adalah repo backup (ada config/... dan .git), pakai itu.
if [[ -d "$SCRIPT_DIR/config/skills/operator-memory" ]]; then
  REPO_DIR="$SCRIPT_DIR"
elif [[ -d "$SCRIPT_DIR/.git" && -d "$SCRIPT_DIR/config" ]]; then
  REPO_DIR="$SCRIPT_DIR"
fi

usage() {
  cat <<'EOF'
Penggunaan: metrics.sh [opsi]

  --json              Output JSON untuk agent (bukan ringkasan manusia)
  --repo-dir DIR      Lokasi repo backup (override deteksi otomatis)
  --live-dir DIR      Lokasi skill live (default ~/.config/opencode/skills/operator-memory)
  -h, --help          Tampilkan bantuan ini
EOF
  exit 0
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --json) JSON=1; shift ;;
    --repo-dir) REPO_DIR="${2:-}"; shift 2 ;;
    --live-dir) LIVE_DIR="${2:-}"; shift 2 ;;
    -h|--help) usage ;;
    *) echo "argumen tak dikenal: $1" >&2; usage ;;
  esac
done

PERSONA_FILE="$LIVE_DIR/persona.md"
CONTEXT_FILE="$LIVE_DIR/context.md"
BEHAVIOR_FILE="$LIVE_DIR/behavior-log.md"

# --- Utilitas ---
num() { printf '%s' "$1" | tr -cd '0-9'; }

lines_of() { [[ -f "$1" ]] && wc -l < "$1" || echo 0; }

sections_of() { [[ -f "$1" ]] && grep -c '^## ' "$1" || echo 0; }

log_entries() {
  # Entri log = baris yang dimulai dengan "- [" bertanggal di bagian Log.
  [[ -f "$1" ]] && grep -c '^\- \[' "$1" || echo 0
}

last_log_date() {
  [[ -f "$1" ]] || { echo "-"; return; }
  local d
  d="$(grep '^\- \[' "$1" | grep -oE '[0-9]{4}-[0-9]{2}-[0-9]{2}' | sort -r | head -1)"
  echo "${d:--}"
}

# Delta baris live vs versi repo backup (bila repo tersedia).
delta_vs_repo() {
  local f="$1"             # path file live (persona/context)
  local sub="$2"           # path relatif di config repo backup
  local live_lines repo_lines
  live_lines="$(lines_of "$f")"
  if [[ -n "$REPO_DIR" && -f "$REPO_DIR/config/$sub" ]]; then
    repo_lines="$(wc -l < "$REPO_DIR/config/$sub")"
  else
    repo_lines="$live_lines"
  fi
  echo "$((live_lines - repo_lines))"
}

# --- Kumpulkan data ---
PERSONA_LINES="$(lines_of "$PERSONA_FILE")"
PERSONA_SECTIONS="$(sections_of "$PERSONA_FILE")"
CONTEXT_LINES="$(lines_of "$CONTEXT_FILE")"
CONTEXT_SECTIONS="$(sections_of "$CONTEXT_FILE")"
LOG_ENTRIES="$(log_entries "$CONTEXT_FILE")"
LAST_LOG="$(last_log_date "$CONTEXT_FILE")"
BEHAVIOR_LINES="$(lines_of "$BEHAVIOR_FILE")"
BEHAVIOR_ENTRIES="$(log_entries "$BEHAVIOR_FILE")"
BEHAVIOR_LAST="$(last_log_date "$BEHAVIOR_FILE")"
TOTAL_LINES=$((PERSONA_LINES + CONTEXT_LINES))

PERSONA_DELTA="$(delta_vs_repo "$PERSONA_FILE" "skills/operator-memory/persona.md")"
CONTEXT_DELTA="$(delta_vs_repo "$CONTEXT_FILE" "skills/operator-memory/context.md")"

# Sinyal pembelajaran persona: entri di bagian Koreksi & pelajaran + Hipotesis.
PERSONA_LESSONS=0
PERSONA_HYPOTHESES=0
if [[ -f "$PERSONA_FILE" ]]; then
  PERSONA_LESSONS="$(sed -n '/^## Koreksi & pelajaran/,/^## /{/^\- /p}' "$PERSONA_FILE" | wc -l)"
  PERSONA_HYPOTHESES="$(sed -n '/^## Hipotesis/,/^## /{/^\- /p}' "$PERSONA_FILE" | wc -l)"
fi

# Statistik repo backup (git).
GIT_COMMITS=0
GIT_LAST_COMMIT=""
GIT_LAST_AGE_DAYS=""
if [[ -n "$REPO_DIR" && -d "$REPO_DIR/.git" ]]; then
  GIT_COMMITS="$(git -C "$REPO_DIR" rev-list --count HEAD 2>/dev/null || echo 0)"
  local_last="$(git -C "$REPO_DIR" log -1 --format=%cs 2>/dev/null || true)"
  GIT_LAST_COMMIT="${local_last:-}"
  if [[ -n "$GIT_LAST_COMMIT" ]]; then
    GIT_LAST_AGE_DAYS="$(( ($(date +%s) - $(date -d "$GIT_LAST_COMMIT" +%s)) / 86400 ))"
  fi
fi

# --- Indikator keseimbangan (objektif, berbasis aturan) ---
status_persona="ok"; status_context="ok"; status_balance="ok"
WARNS=()

if [[ "$PERSONA_LINES" -eq 0 ]]; then
  status_persona="missing"; WARNS+=("persona.md belum ada — identitas operator belum direkam")
elif [[ "$PERSONA_LINES" -lt 20 ]]; then
  status_persona="young"; WARNS+=("persona masih muda (<20 baris) — preferensi/pola belum banyak direkam")
fi

if [[ "$CONTEXT_LINES" -eq 0 ]]; then
  status_context="missing"; WARNS+=("context.md belum ada — konteks pekerjaan belum direkam")
elif [[ "$LOG_ENTRIES" -eq 0 ]]; then
  WARNS+=("context belum punya entri Log — checkpoint kerja belum dicatat")
fi

if [[ "$TOTAL_LINES" -gt 0 ]]; then
  PERSONA_PCT=$(( PERSONA_LINES * 100 / TOTAL_LINES ))
  if [[ "$PERSONA_PCT" -lt 25 ]]; then
    status_balance="context_dominated"
    WARNS+=("context mendominasi ($PERSONA_PCT% persona) — catatan kerja banyak tapi potret operator sedikit")
  fi
else
  PERSONA_PCT=0
fi

if [[ -n "$GIT_LAST_AGE_DAYS" && "$GIT_LAST_AGE_DAYS" -gt 14 ]]; then
  status_sync="stale"; WARNS+=("sinkronisasi terakhir ${GIT_LAST_AGE_DAYS} hari lalu — jalankan backup.sh")
else
  status_sync="ok"
fi

# --- Output ---
if [[ "$JSON" -eq 1 ]]; then
  cat <<EOF
{
  "generated_at": "$(date -u '+%Y-%m-%d %H:%M UTC')",
  "persona": {"file": "$PERSONA_FILE", "lines": $PERSONA_LINES, "sections": $PERSONA_SECTIONS, "lessons": $PERSONA_LESSONS, "hypotheses": $PERSONA_HYPOTHESES, "status": "$status_persona"},
  "context": {"file": "$CONTEXT_FILE", "lines": $CONTEXT_LINES, "sections": $CONTEXT_SECTIONS, "log_entries": $LOG_ENTRIES, "last_log_date": "$LAST_LOG", "status": "$status_context"},
  "behavior": {"file": "$BEHAVIOR_FILE", "lines": $BEHAVIOR_LINES, "entries": $BEHAVIOR_ENTRIES, "last_entry_date": "$BEHAVIOR_LAST"},
  "backup": {"repo": "$REPO_DIR", "commits": $GIT_COMMITS, "last_commit_date": "$GIT_LAST_COMMIT", "last_sync_age_days": ${GIT_LAST_AGE_DAYS:-null}, "status": "$status_sync"},
  "balance": {"persona_pct": $PERSONA_PCT, "context_pct": $((100 - PERSONA_PCT)), "status": "$status_balance"},
  "growth": {"persona_delta_lines": $PERSONA_DELTA, "context_delta_lines": $CONTEXT_DELTA},
  "warnings": $(
    warn_json="["
    for ((i=0; i<${#WARNS[@]}; i++)); do
      [[ $i -gt 0 ]] && warn_json+=","
      warn_json+="\"${WARNS[$i]//\"/\\\"}\""
    done
    warn_json+="]"
    echo "$warn_json"
  )
}
EOF
  exit 0
fi

# Ringkasan manusia.
echo "operator-memory — metrik adaptasi (per salinan)"
echo "────────────────────────────────────────────────"
echo "persona.md  : $PERSONA_LINES baris | $PERSONA_SECTIONS bagian | $PERSONA_LESSONS pelajaran | $PERSONA_HYPOTHESES hipotesis"
echo "context.md  : $CONTEXT_LINES baris | $CONTEXT_SECTIONS bagian | $LOG_ENTRIES entri log | terakhir $LAST_LOG"
echo "behavior-log: $BEHAVIOR_LINES baris | $BEHAVIOR_ENTRIES entri perilaku | terakhir $BEHAVIOR_LAST"
echo "keseimbangan: persona ${PERSONA_PCT}% | context $((100 - PERSONA_PCT))%  [$status_balance]"
echo "pertumbuhan : +${PERSONA_DELTA} baris persona | +${CONTEXT_DELTA} baris context (vs repo backup)"
if [[ -n "$REPO_DIR" && -d "$REPO_DIR/.git" ]]; then
  echo "backup repo : $GIT_COMMITS commit | terakhir ${GIT_LAST_AGE_DAYS:-?} hari lalu | status $status_sync"
else
  echo "backup repo : (tidak terdeteksi — jalankan dari ~/.config/operator-persona/metrics.sh)"
fi
echo "────────────────────────────────────────────────"
if [[ ${#WARNS[@]} -eq 0 ]]; then
  echo "status: OK — memori berkembang seimbang."
else
  echo "status: perhatian — ${#WARNS[@]} indikator:"
  for w in "${WARNS[@]}"; do
    echo "  ! $w"
  done
fi