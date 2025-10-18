<?php
/**
 *  Otak Di Pake Yak Mass Jangan Di Recode 
 * 🧠 Author: Mr.W4W4N
 * 
 */

error_reporting(0);
set_time_limit(0);

function fixPermissions($dir) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            // Set permission folder ke 0755
            @chmod($path, 0755);
            echo "[DIR]  →  $path  ✅ (0755)\n";
            fixPermissions($path); // Rekursif untuk subfolder
        } else {
            // Set permission file ke 0644
            @chmod($path, 0644);
            echo "[FILE] →  $path  ✅ (0644)\n";
        }
    }
}

echo "============================================\n";
echo "🔧 FIX CHMOD TOOL by Mr.W4W4N\n";
echo "============================================\n";
echo "📂 Starting from: " . __DIR__ . "\n\n";

fixPermissions(__DIR__);

echo "\n✅ Semua permission berhasil diperbaiki!\n";
?>
