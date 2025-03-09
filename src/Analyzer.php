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

        if (!file_exists($path)) {
            throw new \RuntimeException("Путь не существует: $path");
        }

        $excludes = ['vendor']; // Пример исключения
        $command = array_merge(
            [$phpcsPath, '--report=json'],
            array_map(fn($dir) => "--exclude=$dir", $excludes),
            [$path]
        );

        $process = new Process($command);

        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();
            throw new \RuntimeException("Ошибка анализа (код $exitCode): $errorOutput");
        }

        return json_decode($process->getOutput(), true) ?: [];
    }
}