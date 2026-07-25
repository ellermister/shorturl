#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/data/ip2region_v4.xdb"
URL="https://github.com/lionsoul2014/ip2region/raw/master/data/ip2region_v4.xdb"
mkdir -p "$ROOT/data"
if [[ -f "$OUT" ]]; then
  echo "already exists: $OUT"
  exit 0
fi
curl -L --fail -o "$OUT" "$URL"
echo "downloaded: $OUT"
