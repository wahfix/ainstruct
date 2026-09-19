#!/bin/sh
# ============================================================================
# AINSTRUCT — curl | sh installer / runner
# ============================================================================
# Mengunduh tarball repo AI-Instructions ke cache lokal, lalu mengeksekusi
# bin/setup-ai-rules.sh dari cache terhadap pwd (proyek konsumen). Tidak
# menginstal apa pun ke sistem.
#
# Penggunaan:
#   curl -fsSL https://raw.githubusercontent.com/wahfix/ainstruct/main/install.sh \
#       | sh -s -- laravel            # distribusikan framework laravel
#   curl -fsSL <...>/install.sh | sh -s -- reset laravel
#   curl -fsSL <...>/install.sh | sh -s -- init --dry-run   # deteksi stack tanpa perubahan
#   curl -fsSL <...>/install.sh | sh -s -- wipe --force
#
# Variabel lingkungan:
#   AINSTRUCT_SOURCE_URL   URL tarball sumber (default: main.tar.gz di GitHub)
#   AINSTRUCT_CACHE        Direktori cache  (default: ${XDG_CACHE_HOME:-$HOME/.cache}/ainstruct)
#   AINSTRUCT_UPDATE=1     Paksa unduh ulang tarball (abaikan cache)
#
# CATATAN: arah kerja = pwd. JANGAN jalankan di dalam repo AI-Instructions
# (repo authoring) — hasilnya menimpa self-instruction repo tersebut.
# ============================================================================

set -eu

DEFAULT_SOURCE_URL="https://github.com/wahfix/ainstruct/archive/refs/heads/main.tar.gz"
SOURCE_URL="${AINSTRUCT_SOURCE_URL:-$DEFAULT_SOURCE_URL}"
DEFAULT_CACHE="${XDG_CACHE_HOME:-$HOME/.cache}/ainstruct"
CACHE_DIR="${AINSTRUCT_CACHE:-$DEFAULT_CACHE}"
UPDATE="${AINSTRUCT_UPDATE:-0}"

# Guard keamanan: cache tidak boleh menunjuk ke lokasi berbahaya.
if [ "$CACHE_DIR" = "/" ]; then
    echo "error: AINSTRUCT_CACHE tidak boleh '/'" >&2
    exit 1
fi

missing=0
command -v curl >/dev/null 2>&1 || missing=1
command -v tar  >/dev/null 2>&1 || missing=1
if [ "$missing" -ne 0 ]; then
    echo "error: install.sh butuh perintah 'curl' dan 'tar'" >&2
    exit 1
fi

# Entry script live di bin/setup-ai-rules.sh dalam tarball/cache.
SETUP_REL="bin/setup-ai-rules.sh"

need_fetch=0
if [ ! -f "$CACHE_DIR/$SETUP_REL" ]; then
    need_fetch=1
fi
if [ "$UPDATE" = "1" ]; then
    need_fetch=1
fi

if [ "$need_fetch" = "1" ]; then
    tmp="${TMPDIR:-/tmp}/ainstruct.$$.tmp"
    trap 'rm -rf "$tmp"' EXIT INT TERM
    rm -rf "$tmp"
    mkdir -p "$tmp"

    echo "→ Downloading: $SOURCE_URL" >&2
    curl -fsSL "$SOURCE_URL" -o "$tmp/ainstruct.tar.gz"

    rm -rf "$CACHE_DIR"
    mkdir -p "$CACHE_DIR"

    # Tarball punya top-level dir tunggal (contoh: codeload 'AI-Instructions-main/')?
    top_count="$(tar -tzf "$tmp/ainstruct.tar.gz" \
        | awk -F'/' 'length($1)>0 {print $1}' \
        | sort -u | wc -l)"

    if [ "$top_count" = "1" ]; then
        # shellcheck disable=SC2015
        tar -xzf "$tmp/ainstruct.tar.gz" -C "$CACHE_DIR" --strip-components=1 || true
    else
        tar -xzf "$tmp/ainstruct.tar.gz" -C "$CACHE_DIR"
    fi

    # Fallback: bila isi masih di dalam satu top-level dir (mis. tar tanpa
    # --strip-components), naikkan isinya ke akar cache.
    if [ ! -f "$CACHE_DIR/$SETUP_REL" ]; then
        topdir=""
        for entry in "$CACHE_DIR"/*/; do
            [ -d "$entry" ] || continue
            if [ -z "$topdir" ]; then
                topdir="$entry"
            else
                topdir=""
                break
            fi
        done
        if [ -n "$topdir" ]; then
            (cd "$topdir" && find . -mindepth 1 -maxdepth 1 -exec mv {} "$CACHE_DIR"/ \;)
            rmdir "$topdir" 2>/dev/null || true
        fi
    fi

    if [ ! -f "$CACHE_DIR/$SETUP_REL" ]; then
        echo "error: tarball tidak berisi $SETUP_REL (URL sumber salah?)" >&2
        exit 1
    fi

    echo "→ Cache: $CACHE_DIR" >&2
fi

exec "$CACHE_DIR/$SETUP_REL" "$@"