<?php
/*
Written by GhostHaxor <suryaheck@gmail.com>, November 2024
Copyright (C) 2024  GhostHaxor

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
function scanDirectory($dir, &$infectedFiles, &$errors) {
    // Cek apakah direktori bisa dibaca
    if (!is_readable($dir)) {
        $errors[] = "Direktori tidak bisa dibaca (permission denied): " . $dir;
        return;
    }
    
    $files = scandir($dir);
    if ($files === false) {
        $errors[] = "Gagal scan direktori: " . $dir;
        return;
    }
    
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $path = $dir . "/" . $file;
            if (is_dir($path)) {
                scanDirectory($path, $infectedFiles, $errors);
            } else {
                $fileExtension = pathinfo($path, PATHINFO_EXTENSION);
                if (in_array($fileExtension, getValidExtensions()) && isFileInfected($path)) {
                    $infectedFiles[] = $path;
                }
            }
        }
    }
}

function isFileInfected($file) {
    // Cek apakah file bisa dibaca
    if (!is_readable($file)) {
        return false;
    }
    
    $malwareSignatures = array(
        "eval(",
        "exec(",
        "shell_exec(",
        "system(",
        "passthru(",
        "pcntl_fork(",
        "fsockopen(",
        "proc_open(",
        "popen(",
        "assert(",
        "posix_kill(",
        "posix_setpgid(",
        "posix_setsid(",
        "posix_setuid(",
        "proc_nice(",
        "proc_close(",
        "proc_terminate(",
        "apache_child_terminate(",
        "posix_getuid(",
        "posix_geteuid(",
        "posix_getegid(",
        "posix_getpwuid(",
        "posix_getgrgid(",
        "posix_mkfifo(",
        "posix_getlogin(",
        "posix_ttyname(",
        "getenv(",
        "proc_get_status(",
        "get_cfg_var(",
        "disk_free_space(",
        "disk_total_space(",
        "diskfreespace(",
        "getlastmo(",
        "getmyinode(",
        "getmypid(",
        "getmyuid(",
        "getmygid(",
        "fileowner(",
        "filegroup(",
        "get_current_user(",
        "pathinfo(",
        "getcwd(",
        "sys_get_temp_dir(",
        "basename(",
        "phpinfo(",
        "mysql_connect(",
        "mysqli_connect(",
        "mysqli_query(",
        "mysql_query(",
        "fopen(",
        "fsockopen(",
        "file_put_contents(",
        "file_get_contents(",
        "url_get_contents(",
        "stream_get_meta_data(",
        "move_uploaded_file(",
        "$_files(",
        "copy(",
        "include(",
        "include_once(",
        "require(",
        "require_once(",
        "file(",
        "mail(",
        "putenv(",
        "curl_init(",
        "tmpfile(",
        "allow_url_fopen(",
        "ini_set(",
        "set_time_limit(",
        "session_start(",
        "symlink(",
        "halt_compiler(",
        "__compiler_halt_offset(",
        "error_reporting(",
        "create_function(",
        "get_magic_quotes_gpc(",
        "$auth_pass(",
        "$password("
    );
    
    $fileContent = file_get_contents($file);
    if ($fileContent === false) {
        return false;
    }
    
    foreach ($malwareSignatures as $signature) {
        if (strpos($fileContent, $signature) !== false) {
            return true;
        }
    }
    return false;
}

function getValidExtensions() {
    return array(
        "php",
        "phps",
        "pht",
        "phpt",
        "phtml",
        "phar",
        "php3",
        "php4",
        "php5",
        "php7",
        "php8",
        "suspected"
    );
}

function displayInfectedFiles($infectedFiles) {
    foreach ($infectedFiles as $infectedFile) {
        echo "File terinfeksi: " . $infectedFile . PHP_EOL;
        echo "Isi file (5 baris atas & 5 baris bawah):" . PHP_EOL;
        $lines = file($infectedFile);
        $totalLines = count($lines);
        
        for ($i = 0; $i < 5 && $i < $totalLines; $i++) {
            echo $lines[$i];
        }
        
        if ($totalLines > 10) {
            echo "... (total baris: $totalLines) ..." . PHP_EOL;
        }
        
        $start = max(0, $totalLines - 5);
        for ($i = $start; $i < $totalLines; $i++) {
            if ($totalLines <= 10 || $i >= 5) {
                echo $lines[$i];
            }
        }
        echo "----------------------" . PHP_EOL;
    }
}

$currentDirectory = dirname(__FILE__);
$infectedFiles = array();
$errors = array();
scanDirectory($currentDirectory, $infectedFiles, $errors);

displayInfectedFiles($infectedFiles);

$outputFile = "daki-ini.txt";
$fileHandle = fopen($outputFile, "w");
foreach ($infectedFiles as $infectedFile) {
    fwrite($fileHandle, "File terinfeksi: " . $infectedFile . PHP_EOL);
    fwrite($fileHandle, "Isi file (5 baris atas & 5 baris bawah):" . PHP_EOL);
    $lines = file($infectedFile);
    $totalLines = count($lines);
    
    for ($i = 0; $i < 5 && $i < $totalLines; $i++) {
        fwrite($fileHandle, $lines[$i]);
    }
    
    if ($totalLines > 10) {
        fwrite($fileHandle, "... (total baris: $totalLines) ..." . PHP_EOL);
    }
    
    $start = max(0, $totalLines - 5);
    for ($i = $start; $i < $totalLines; $i++) {
        if ($totalLines <= 10 || $i >= 5) {
            fwrite($fileHandle, $lines[$i]);
        }
    }
    fwrite($fileHandle, "----------------------" . PHP_EOL);
}

// Tulis error di Paling Bawah
if (count($errors) > 0) {
    fwrite($fileHandle, PHP_EOL . "========== ERROR (GAGAL SCAN) ==========" . PHP_EOL);
    foreach ($errors as $error) {
        fwrite($fileHandle, $error . PHP_EOL);
    }
    fwrite($fileHandle, "==========================================" . PHP_EOL);
}

fclose($fileHandle);

echo "Output telah disimpan dalam file " . $outputFile . ".";
if (count($errors) > 0) {
    echo " Terdapat " . count($errors) . " error (permission/dll). Cek file " . $outputFile . " untuk detail.";
}
?>
