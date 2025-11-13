#!/usr/bin/env php
<?php

class HtaccessComparator {
    private $htaccessFiles = [];
    private $htaccessContents = [];
    private $startDir;
    
    public function __construct($directory = null) {
        $this->startDir = $directory ?: getcwd();
        $this->showBanner();
    }
    
    private function showBanner() {
        echo "\033[1;36m" . "
  _    _ _____   _____      _____  _    _ ______ _____  
 | |  | |  __ \ / ____|    / ____|| |  | |  ____|  __ \ 
 | |__| | |__) | |        | |     | |__| | |__  | |__) |
 |  __  |  _  /| |        | |     |  __  |  __| |  _  / 
 | |  | | | \ \| |____    | |____ | |  | | |____| | \ \ 
 |_|  |_|_|  \_\\_____|    \_____||_|  |_|______|_|  \_\
                                                        
        \033[0m\n";
        echo "\033[1;33mTools Pencari File .htaccess yang Berbeda\033[0m\n";
        echo "==========================================\n\n";
    }
    
    public function findHtaccessFiles() {
        echo "🔍 Mencari file .htaccess di: " . realpath($this->startDir) . "\n";
        
        if (!is_dir($this->startDir)) {
            echo "❌ Error: Directory tidak ditemukan!\n";
            exit(1);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->startDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        $found = 0;
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === '.htaccess') {
                $this->htaccessFiles[] = $file->getPathname();
                $found++;
            }
        }
        
        echo "📁 Ditemukan " . $found . " file .htaccess\n\n";
        
        if ($found === 0) {
            echo "❌ Tidak ada file .htaccess yang ditemukan!\n";
            exit(0);
        }
    }
    
    public function readHtaccessContents() {
        foreach ($this->htaccessFiles as $file) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $this->htaccessContents[$file] = [
                    'content' => $content,
                    'hash' => md5($content),
                    'size' => strlen($content),
                    'lines' => substr_count($content, "\n") + 1
                ];
            } else {
                echo "⚠️  Gagal membaca file: $file\n";
            }
        }
    }
    
    public function groupByContent() {
        $groups = [];
        
        foreach ($this->htaccessContents as $file => $data) {
            $hash = $data['hash'];
            if (!isset($groups[$hash])) {
                $groups[$hash] = [
                    'content' => $data['content'],
                    'files' => [],
                    'size' => $data['size'],
                    'lines' => $data['lines']
                ];
            }
            $groups[$hash]['files'][] = $file;
        }
        
        return $groups;
    }
    
    public function displayDifferentGroupsOnly() {
        $groups = $this->groupByContent();
        
        echo "\033[1;32m📊 HASIL ANALISIS:\033[0m\n";
        echo "==================\n\n";
        
        if (count($groups) === 1) {
            echo "✅ \033[1;33mSEMUA file .htaccess memiliki konten yang SAMA!\033[0m\n";
            echo "📍 Tidak ada perbedaan yang ditemukan.\n";
            return;
        }
        
        echo "⚠️  \033[1;31mDitemukan " . count($groups) . " jenis konten .htaccess yang BERBEDA!\033[0m\n\n";
        
        $groupNumber = 1;
        foreach ($groups as $hash => $group) {
            $fileCount = count($group['files']);
            
            echo "\033[1;35m" . "--- GRUP $groupNumber ---\033[0m\n";
            echo "📂 Jumlah file dalam grup: $fileCount file\n";
            echo "📏 Ukuran: " . $group['size'] . " bytes\n";
            echo "📄 Baris: " . $group['lines'] . " lines\n";
            echo "📍 Lokasi file:\n";
            
            foreach ($group['files'] as $file) {
                $relativePath = str_replace(realpath($this->startDir) . '/', '', $file);
                echo "   • \033[0;34m" . $relativePath . "\033[0m\n";
            }
            
            echo "\n📝 Isi file .htaccess:\n";
            echo "\033[0;90m" . "----------------------------------------\033[0m\n";
            
            if (empty(trim($group['content']))) {
                echo "\033[0;33m(File kosong)\033[0m\n";
            } else {
                $lines = explode("\n", $group['content']);
                foreach ($lines as $index => $line) {
                    $lineNumber = $index + 1;
                    echo "\033[0;90m" . str_pad($lineNumber, 3) . " | \033[0m" . $line . "\n";
                }
            }
            
            echo "\033[0;90m" . "----------------------------------------\033[0m\n\n";
            
            // Tampilkan perbedaan dengan grup sebelumnya (jika ada)
            if ($groupNumber > 1) {
                $this->showDiffWithPrevious($groups, $groupNumber);
            }
            
            $groupNumber++;
        }
    }
    
    private function showDiffWithPrevious($groups, $currentGroupNum) {
        $currentGroup = array_slice($groups, $currentGroupNum - 1, 1);
        $prevGroup = array_slice($groups, $currentGroupNum - 2, 1);
        
        $currentContent = trim(reset($currentGroup)['content']);
        $prevContent = trim(reset($prevGroup)['content']);
        
        if ($currentContent !== $prevContent) {
            echo "🔄 \033[1;33mPerbedaan dengan grup sebelumnya:\033[0m\n";
            
            $currentLines = explode("\n", $currentContent);
            $prevLines = explode("\n", $prevContent);
            
            $maxLines = max(count($currentLines), count($prevLines));
            
            for ($i = 0; $i < $maxLines; $i++) {
                $currentLine = $currentLines[$i] ?? '';
                $prevLine = $prevLines[$i] ?? '';
                
                if ($currentLine !== $prevLine) {
                    echo "   Baris " . ($i + 1) . ":\n";
                    if ($prevLine !== '') {
                        echo "     \033[0;31m- " . $prevLine . "\033[0m\n";
                    }
                    if ($currentLine !== '') {
                        echo "     \033[0;32m+ " . $currentLine . "\033[0m\n";
                    }
                }
            }
            echo "\n";
        }
    }
    
    public function run() {
        $this->findHtaccessFiles();
        $this->readHtaccessContents();
        $this->displayDifferentGroupsOnly();
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die("❌ Tools ini harus dijalankan di terminal/command line!\n");
}

try {
    $searchDir = $argv[1] ?? getcwd();
    $comparator = new HtaccessComparator($searchDir);
    $comparator->run();
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
