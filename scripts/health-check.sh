#!/usr/bin/env bash
#
# health-check.sh — Instruction Repository Integrity Check
#
# Verifies the AI-Instructions authoring repository stays consistent:
#   1. Every path token used in backticks across .md files resolves to an
#      existing file (no broken cross-references / "phantom" modules).
#   2. Every instruction file under every templates/<Framework>/ directory is
#      git-tracked (a tracked file is not affected by .gitignore ignores and
#      reaches PRs).
#   3. No generated distribution artifacts are present in the repo root.
#   4. Anti-AI-slop gate (scripts/antislop-check.sh) — dokumen authoring bebas
#      pola marketing/AI-slop (buzzword, klaim tanpa bukti, frase generik).
#
# Frameworks are auto-discovered: any non-hidden directory under templates/
# that contains an ai-instructions.md marker file (mirrors ainstruct).
#
# Usage: scripts/health-check.sh [--quiet]
# Exit code 0 = all checks pass; non-zero = violations found.

set -uo pipefail

QUIET=0
if [[ "${1:-}" == "--quiet" ]]; then
  QUIET=1
fi

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FAILED=0
PASSED=0

say() {
  if [[ $QUIET -eq 0 ]]; then printf '%s\n' "$*"; fi
}

fail() {
  FAILED=$((FAILED + 1))
  printf '  [FAIL] %s\n' "$*" >&2
}

ok() {
  PASSED=$((PASSED + 1))
  say "  [ok] $*"
}

# Paths that exist only in consumer projects (referenced in instructions) or that
# name forbidden generated artifacts — never resolved against this repo.
SKIP_OR_EXTERNAL='^(app/|resources/|routes/|database/|config/|tests/|vendor/|public/|bootstrap/|node_modules/|stories/|\.github/|\.cursor/|\.clinerules/|\.opencode/|CLAUDE\.md|GEMINI\.md|\.cursorrules|\.windsurfrules|\.continuerules|\.aider\.conf\.yml|opencode\.json|AGENTS\.md|README\.md)'

cd "$ROOT" || exit 1

# Discover framework directories (any non-hidden dir under templates/ with an ai-instructions.md).
mapfile -t framework_dirs < <(
  while IFS= read -r d; do
    d="${d#./}"
    [[ -d "$d" && -f "$d/ai-instructions.md" ]] && printf '%s\n' "$d"
  done < <(find templates -mindepth 1 -maxdepth 1 -type d -not -name '.*' 2>/dev/null)
)

if [[ ${#framework_dirs[@]} -eq 0 ]]; then
  printf 'health-check: tidak ada framework dir yang ditemukan (butuh templates/<Framework>/ai-instructions.md).\n' >&2
  exit 1
fi

say "== Framework terdeteksi: ${framework_dirs[*]} =="
say ""

say "== Referensi silang antar file instruksi =="

# Collect every backtick-coded path token from repo-root and framework markdown files.
# Vendor skills hidup di <Framework>/opencode/ (mirror .opencode/ root) — konten
# pihak ketiga, bukan anchor instruksi → dilewati seperti .opencode/.
mapfile -t md_files < <(find . -maxdepth 1 -name '*.md')
for f in "${framework_dirs[@]}"; do
  while IFS= read -r m; do
    md_files+=("$m")
  done < <(find "$f" -name '*.md' -not -path '*/opencode/*')
done

TOKENS_TMP="$(mktemp)"
for f in "${md_files[@]}"; do
  # shellcheck disable=SC2016
  grep -hoE '`[^`]+\.(md|sh)`' "$f" >> "$TOKENS_TMP" 2>/dev/null || true
done
sort -u "$TOKENS_TMP" -o "$TOKENS_TMP"
# shellcheck disable=SC2016
mapfile -t refs < <(sed -E 's/^`//; s/`$//' "$TOKENS_TMP")
rm -f "$TOKENS_TMP"

TOKEN_PATTERN='^[A-Za-z0-9._/-]+\.(md|sh)$'
for token in "${refs[@]}"; do
  # Skip external / non-file / forbidden-artifact references.
  [[ "$token" =~ $TOKEN_PATTERN ]] || continue
  [[ "$token" =~ http(s)?:// ]] && continue
  [[ "$token" == 'MASTER_BUILD_SPECIFICATION.md' ]] && continue
  # DESIGN.md = arah visual milik proyek konsumen (konsep antislop), bukan anchor repo.
  [[ "$token" == 'DESIGN.md' ]] && continue
  [[ $token =~ $SKIP_OR_EXTERNAL ]] && continue

  resolved=""

  # The constitution is referenced as a bare filename; resolve to any framework.
  if [[ "$token" == 'ai-instructions.md' ]]; then
    for f in "${framework_dirs[@]}"; do
      if [[ -n "$resolved" ]]; then break; fi
      [[ -f "$f/ai-instructions.md" ]] && resolved="$f/ai-instructions.md"
    done
  fi

  # Framework-relative candidates: F/$token, F/ai-instructions/$token.
  for f in "${framework_dirs[@]}"; do
    if [[ -n "$resolved" ]]; then break; fi
    [[ -f "$f/$token" ]] && resolved="$f/$token"
    if [[ -z "$resolved" && -f "$f/ai-instructions/$token" ]]; then
      resolved="$f/ai-instructions/$token"
    fi
  done

  # Repo-root fallback (scripts, bin, etc.).
  if [[ -z "$resolved" && -f "$token" ]]; then
    resolved="$token"
  fi

  # Basename fallback: bare filename that lives somewhere in any framework dir.
  if [[ -z "$resolved" ]]; then
    basename_match="$(find "${framework_dirs[@]}" -type f -name "$token" 2>/dev/null | head -1)"
    if [[ -n "$basename_match" ]]; then
      resolved="$basename_match"
    fi
  fi

  if [[ -z "$resolved" ]]; then
    fail "referensi tdk ditemukan: \`$token\`"
  else
    ok "\`$token\` -> $resolved"
  fi
done

say ""
say "== File instruksi ter-track (bukan phantom) =="

for f in "${framework_dirs[@]}"; do
  while IFS= read -r file; do
    if git ls-files --error-unmatch "$file" >/dev/null 2>&1; then
      ok "tracked $file"
    else
      fail "TIDAK tracked (di-ignore .gitignore): $file"
    fi
  done < <(find "$f" \( -name '*.md' -o -name '*.json' \) | sort)
done

say ""
say "== Artefak distribusi tidak boleh ada di root =="

# Artefak hasil distribusi dilarang di root authoring, KECUALI bila memang
# file milik repo ini (ter-track git) — mis. .opencode/ agent + config default.
while IFS= read -r artifact; do
  if [[ -e "$artifact" ]]; then
    if [[ -n "$(git ls-files -- "$artifact")" ]]; then
      ok "authoring-owned (tracked): $artifact"
    else
      fail "artefak distribusi ada di root: $artifact"
    fi
  else
    ok "tidak ada $artifact"
  fi
done < <(printf '%s\n' CLAUDE.md GEMINI.md .cursorrules .windsurfrules .continuerules .clinerules .cursor .aider.conf.yml .github/copilot-instructions.md opencode.json .opencode)

say ""
say "== Anti-AI-slop gate (dokumen authoring) =="

# Gate pola AI-slop: konten vendor (`.opencode/`, `<Framework>/opencode/`) dikecualikan di dalam script.
if bash scripts/antislop-check.sh; then
  ok "anti-slop"
else
  fail "anti-slop: pola AI-slop ditemukan (lihat output antislop-check)"
fi

say ""
if [[ $FAILED -gt 0 ]]; then
  printf 'health-check: %d GAGAL, %d lulus\n' "$FAILED" "$PASSED" >&2
  exit 1
fi
printf 'health-check: semua lulus (%d)\n' "$PASSED"
exit 0