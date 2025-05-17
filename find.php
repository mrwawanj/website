<?php
// URL tempat list keyword berada
$url_keyword = isset($_GET['list']) ? $_GET['list'] : '';

if (empty($url_keyword)) {
    echo "Tambahkan parameter ?list=URL_LIST_KEYWORD, contoh: ?list=https://paste.ee/r/k2gAuv9E/0\n";
    exit;
}

// Ambil isi keyword dari URL
$keywords_raw = @file_get_contents($url_keyword);
if (!$keywords_raw) {
    echo "Gagal mengambil daftar dari URL: $url_keyword\n";
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

// Jalankan pencarian
cari_kata_dalam_file($folder_mulai, $keywords, $hasil);

// Tampilkan hasil
if ($hasil) {
    echo "=== Hasil Pencarian dari daftar: '$url_keyword' ===\n\n";
    foreach ($hasil as $item) {
        echo "Keyword: {$item['keyword']}\n";
        echo "File   : {$item['file']}\n";
        echo "Baris  : {$item['baris']}\n";
        echo "Kode   : {$item['isi']}\n";
        echo "-----------------------------\n";
    }
} else {
    echo "Tidak ditemukan keyword dari daftar di direktori $folder_mulai\n";
}
