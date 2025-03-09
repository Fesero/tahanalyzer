<?php

namespace Fesero\Tahanalyzer;

use Symfony\Component\Process\Process;

class Analyzer
{
    public function runAnalysis(string $path): array
    {
        // Используем путь относительно текущего пакета
        $phpcsPath = __DIR__ . '/../../../squizlabs/php_codesniffer/bin/phpcs';
        
        if (!file_exists($phpcsPath)) {
            throw new \RuntimeException('PHP_CodeSniffer не найден. Установите зависимости через composer install.');
        }

        $process = new Process([$phpcsPath, '--report=json', $path]);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Analysis failed: ' . $process->getErrorOutput());
        }

        return json_decode($process->getOutput(), true) ?: [];
    }
}