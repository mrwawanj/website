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

function scanDirectory($dir, &$infectedFiles) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $path = $dir . "/" . $file;
            if (is_dir($path)) {
                scanDirectory($path, $infectedFiles);
            } else {
                $fileExtension = pathinfo($path, PATHINFO_EXTENSION);
                if (in_array($fileExtension, getValidExtensions())) {
                    $malwareLines = isFileInfected($path);
                    if (!empty($malwareLines)) {
                        $infectedFiles[$path] = $malwareLines;
                    }
                }
            }
        }
    }
}

function isFileInfected($file) {
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
    
    $fileContent = file($file);
    $malwareLines = array();
    
    foreach ($fileContent as $lineNumber => $line) {
        foreach ($malwareSignatures as $signature) {
            if (strpos($line, $signature) !== false) {
                $malwareLines[] = array(
                    'line_number' => $lineNumber + 1,
                    'content' => $line,
                    'signature' => $signature
                );
                break; // Stop checking other signatures for this line
            }
        }
    }
    
    return $malwareLines;
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
    foreach ($infectedFiles as $filePath => $malwareLines) {
        echo "File terinfeksi: " . $filePath . PHP_EOL;
        echo "Baris yang mengandung backdoor:" . PHP_EOL;
        foreach ($malwareLines as $malwareLine) {
            echo "Baris " . $malwareLine['line_number'] . " (" . $malwareLine['signature'] . "): " . trim($malwareLine['content']) . PHP_EOL;
        }
        echo "----------------------" . PHP_EOL;
    }
}

$currentDirectory = dirname(__FILE__);
$infectedFiles = array();
scanDirectory($currentDirectory, $infectedFiles);

// Tampilkan di layar
displayInfectedFiles($infectedFiles);

// Simpan ke file
$outputFile = "daki-ini.txt";
$fileHandle = fopen($outputFile, "w");

if (empty($infectedFiles)) {
    fwrite($fileHandle, "Tidak ditemukan file yang mengandung backdoor." . PHP_EOL);
} else {
    foreach ($infectedFiles as $filePath => $malwareLines) {
        fwrite($fileHandle, "File terinfeksi: " . $filePath . PHP_EOL);
        fwrite($fileHandle, "Baris yang mengandung backdoor:" . PHP_EOL);
        foreach ($malwareLines as $malwareLine) {
            fwrite($fileHandle, "Baris " . $malwareLine['line_number'] . " (" . $malwareLine['signature'] . "): " . trim($malwareLine['content']) . PHP_EOL);
        }
        fwrite($fileHandle, "----------------------" . PHP_EOL);
    }
}

fclose($fileHandle);

echo "Output telah disimpan dalam file " . $outputFile . ".";
echo "Total file terinfeksi: " . count($infectedFiles) . " file.";
?>
