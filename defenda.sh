#!/usr/bin/env bash
# kill-procs-and-cron.sh
# Deskripsi: Deteksi user target, paksa kill proses (php, bash, perl, pl), lalu hapus crontab user.

set -euo pipefail

if ! command -v pkill >/dev/null 2>&1 || ! command -v pgrep >/dev/null 2>&1 || ! command -v crontab >/dev/null 2>&1; then
  echo "Error: pkill/pgrep/crontab tidak ditemukan. Pastikan paket procps & cron terinstall." >&2
  exit 1
fi

# Deteksi user target
TARGET_USER="${SUDO_USER:-}"
if [ -z "$TARGET_USER" ] || [ "$TARGET_USER" = "root" ]; then
  if command -v logname >/dev/null 2>&1; then
    LOGNAME_VAL="$(logname 2>/dev/null || true)"
    if [ -n "$LOGNAME_VAL" ] && [ "$LOGNAME_VAL" != "root" ]; then
      TARGET_USER="$LOGNAME_VAL"
    fi
  fi
fi
if [ -z "$TARGET_USER" ]; then
  TARGET_USER="$(whoami 2>/dev/null || echo "")"
fi

if [ -z "$TARGET_USER" ]; then
  echo "Gagal mendeteksi user target." >&2
  exit 1
fi

echo "Target user yang terdeteksi: $TARGET_USER"

# Proses yang akan dibunuh
PROCESS_NAMES=(php bash perl pl)

echo
echo "Proses milik user $TARGET_USER yang cocok sebelum pkill -9:"
ANY_FOUND=0
for p in "${PROCESS_NAMES[@]}"; do
  pids=$(pgrep -u "$TARGET_USER" -a -f "$p" 2>/dev/null || true)
  if [ -n "$pids" ]; then
    ANY_FOUND=1
    echo "=== pattern: $p ==="
    echo "$pids"
  fi
done

if [ "$ANY_FOUND" -eq 0 ]; then
  echo "(Tidak ditemukan proses untuk user $TARGET_USER)."
else
  echo
  echo "Menjalankan pkill -9..."
fi

for p in "${PROCESS_NAMES[@]}"; do
  if pkill -9 -u "$TARGET_USER" -f "$p" 2>/dev/null; then
    echo "pkill -9 -u $TARGET_USER -f $p => OK"
  else
    echo "pkill -9 -u $TARGET_USER -f $p => tidak ada proses yang dihentikan"
  fi
done

echo
echo "Menghapus semua cron job milik user $TARGET_USER..."
if crontab -l -u "$TARGET_USER" >/dev/null 2>&1; then
  crontab -r -u "$TARGET_USER"
  echo "Cron job user $TARGET_USER berhasil dihapus."
else
  echo "User $TARGET_USER tidak memiliki cron job."
fi

echo
echo "Selesai."
