<?php
function scanDirectory($dir, $logFile) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $path = $dir . "/" . $file;
            if (is_dir($path)) {
                scanDirectory($path, $logFile);
            } else {
                $fileExtension = pathinfo($path, PATHINFO_EXTENSION);
                if ($fileExtension === "php" && isFileInfected($path)) {
                    $infectedFileMessage = "File terinfeksi: " . $path . PHP_EOL;
                    echo "\033[31m" . $infectedFileMessage . "\033[0m";
                    file_put_contents($logFile, $infectedFileMessage, FILE_APPEND);
                }
            }
        }
    }
}

function isFileInfected($file) {
    $malwareSignatures = array("eval(", "base64_decode(", "system(", "exec(", "shell_exec(");
    $fileContent = file_get_contents($file);
    foreach ($malwareSignatures as $signature) {
        if (strpos($fileContent, $signature) !== false) {
            return true;
        }
    }
    return false;
}

$currentDirectory = dirname(__FILE__);
$logFile = 'scan_results.txt'; // Nama file log tempat menyimpan hasil scan
scanDirectory($currentDirectory, $logFile);

echo "Hasil scan disimpan di: " . $logFile . PHP_EOL;
?>
