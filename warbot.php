<?php
$directory = getcwd(); 
$snapshot_file = "/tmp/snapshot.txt"; 
$log_file = $directory . "/deleted_files.log"; 
$interval = 1; 

$bot_token = "7891220549:AAHvADsByi_6Q_iDdT3EjIIXi5NQPDfABB0";
$chat_id = "1515884619";
$send_photo_url = "https://api.telegram.org/bot$bot_token/sendPhoto";

function sendToTelegram($message, $photo_url)
{
    global $send_photo_url, $chat_id;

    $bold_message = "*Clear File Baru\\!*"; // Escape karakter '!'
    $escaped_message = str_replace(
        ['\\', '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
        array_map(fn($c) => '\\' . $c, ['\\', '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!']),
        $message
    );
    $formatted_message = "$bold_message\n\nFile Baru Terdeteksi: `$escaped_message`";

    $payload = [
        'chat_id' => $chat_id,
        'photo' => $photo_url,
        'caption' => $formatted_message,
        'parse_mode' => 'MarkdownV2' // Gunakan MarkdownV2
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $send_photo_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    if ($response === false) {
        error_log("Gagal mengirim pesan ke Telegram: " . curl_error($ch));
    } else {
        error_log("Respons dari Telegram: " . $response);
    }
    curl_close($ch);
}

function createInitialSnapshot()
{
    global $snapshot_file, $directory;

    $snapshot = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getFilename() !== basename($GLOBALS['log_file'])) {
            $snapshot[] = $file->getRealPath();
        }
    }
    file_put_contents($snapshot_file, implode("\n", $snapshot));
    echo "Snapshot awal berhasil dibuat di $snapshot_file.\n";
}

function loadSnapshot()
{
    global $snapshot_file;

    if (!file_exists($snapshot_file)) {
        return [];
    }
    return array_map('trim', file($snapshot_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
}

function monitor()
{
    global $snapshot_file, $log_file, $directory;

    $initial_files = loadSnapshot();
    $log = fopen($log_file, "a");

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getFilename() !== basename($log_file)) {
            $file_path = $file->getRealPath();

            if ($file->getFilename() === 'error_log') {
                continue;
            }

            if (!in_array($file_path, $initial_files)) {
                echo "File baru terdeteksi: $file_path\n";
                fwrite($log, $file_path . PHP_EOL);

                $image_url = "https://i.ibb.co.com/YBtpcTW/DALL-E-2024-11-26-11-55-45-A-super-high-resolution-ultra-detailed-illustration-of-a-PHP-script-for-f.webp"; // URL gambar yang valid
                sendToTelegram($file_path, $image_url);

                if (!unlink($file_path)) {
                    error_log("Gagal menghapus file $file_path");
                }
            }
        }
    }
    fclose($log);
}

if (!file_exists($snapshot_file)) {
    createInitialSnapshot();
}

while (true) {
    monitor();
    sleep($interval);
}
?>
