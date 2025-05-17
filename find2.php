<?php
// URL tetap (bisa kamu ganti ke URL lain)
$url = 'https://paste.ee/r/udfqrWd8/0';

$keywords_raw = @file_get_contents($url);
if (!$keywords_raw) {
    echo "Gagal mengambil daftar dari URL: $url\n";
    exit;
}

$keywords = array_filter(array_map('trim', explode("\n", $keywords_raw)));
if (empty($keywords)) {
    echo "Daftar keyword kosong.\n";
    exit;
}

$folder_mulai = __DIR__;
$hasil = [];

function cari_kata_dalam_file($dir, $keywords, &$hasil) {
    $daftar = scandir($dir);
    foreach ($daftar as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            cari_kata_dalam_file($path, $keywords, $hasil);
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $cek_ext = ['php', 'html', 'js', 'css', 'txt'];
            if (in_array($ext, $cek_ext)) {
                $baris = @file($path);
                if ($baris) {
                    foreach ($baris as $i => $line) {
                        foreach ($keywords as $kw) {
                            if (stripos($line, $kw) !== false) {
                                $hasil[] = [
                                    'file' => $path,
                                    'baris' => $i + 1,
                                    'isi' => trim($line),
                                    'keyword' => $kw
                                ];
                            }
                        }
                    }
                }
            }
        }
    }
}

cari_kata_dalam_file($folder_mulai, $keywords, $hasil);

if ($hasil) {
    echo "\n=== Hasil Pencarian dari: '$url' ===\n\n";
    foreach ($hasil as $item) {
        echo "\033[32mKeyword: {$item['keyword']}\n";
        echo "File   : {$item['file']}\n";
        echo "Baris  : {$item['baris']}\n";
        echo "Kode   : {$item['isi']}\033[0m\n";
        echo "-----------------------------\n";
    }
} else {
    echo "Tidak ditemukan keyword dari daftar di direktori $folder_mulai\n";
}
