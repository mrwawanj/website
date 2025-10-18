<?php
function scanDirectory($dir, $logFile) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $path = $dir . "/" . $file;
            if (is_file($path)) {
                $fileExtension = pathinfo($path, PATHINFO_EXTENSION);
                if ($fileExtension === "php" && isFileInfected($path)) {
                    $infectedFileMessage = "File terinfeksi: " . $path . PHP_EOL;
                    echo "\033[31m" . $infectedFileMessage . "\033[0m";

                    // Tampilkan 30 baris pertama kode mencurigakan
                    $lines = file($path);
                    $snippet = implode("", array_slice($lines, 0, 30));
                    echo "\033[33m----- Cuplikan 30 baris pertama -----\033[0m\n";
                    echo $snippet . "\n";
                    echo "\033[33m------------------------------------\033[0m\n\n";

                    // Simpan hasil ke log
                    file_put_contents($logFile, $infectedFileMessage . $snippet . PHP_EOL, FILE_APPEND);
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

$currentDirectory = __DIR__;
$logFile = 'aaas.txt'; // Nama file log tempat menyimpan hasil scan
scanDirectory($currentDirectory, $logFile);

echo "Hasil scan disimpan di: " . $logFile . PHP_EOL;
?>
