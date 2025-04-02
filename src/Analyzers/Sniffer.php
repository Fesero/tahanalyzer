<?php

namespace Fesero\Tahanalyzer\Analyzers;

use Fesero\Tahanalyzer\Factory\AbstractAnalyzer;

class Sniffer extends AbstractAnalyzer
{
    public function run(): array
    {
        // Используем путь относительно проекта
        $phpcsPath = $this->normalizePath($this->root . '/vendor/bin/phpcs');
        
        if (!file_exists($phpcsPath)) {
            throw new \RuntimeException('PHP_CodeSniffer не найден. Установите зависимости через composer install.');
        }

        if (!file_exists($this->path)) {
            throw new \RuntimeException("Путь не существует: $this->path");
        }

        // Проверяем наличие конфигурации
        if (!file_exists($this->configPath)) {
            throw new \RuntimeException("Файл конфигурации phpcs.xml не найден. Запустите composer install для его создания.");
        }

        $excludeArgs = array_map(fn($dir) => "--ignore={$dir}", $this->exclude);
        $command = array_merge(
            [
                $phpcsPath,
                "-d", "memory_limit=512M",
                '--report=json',
                "--standard={$this->configPath}"
            ],
            $excludeArgs,
            [$this->path]
        );

        $process = $this->createProcess($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $exitCode = $process->getExitCode();
            if ($exitCode === 2) {
                $result = json_decode($process->getOutput(), true);
                return $result ?: ['files' => [], 'totals' => ['errors' => 0, 'warnings' => 0]];
            }
            $errorOutput = $process->getErrorOutput();
            $output = $process->getOutput();
            throw new \RuntimeException("Ошибка анализа (код $exitCode): $errorOutput\n$output");
        }

        return ['files' => [], 'totals' => ['errors' => 0, 'warnings' => 0]];
    }
}