<?php

namespace Fesero\Tahanalyzer;

use Symfony\Component\Process\Process;

class Analyzer
{
    private $exclude;
    private $configPath;

    public function __construct(array $exclude = [])
    {
        $this->exclude = $exclude;
        $this->configPath = getcwd() . '/phpcs.xml';
    }

    public function runAnalyze(string $path, string $type): array
    {
        return match($type) {
            'sniffer' => $this->runSniffer($path),
            'phpstan' => $this->runPHPStan($path)
        };
    }

    private function runSniffer(string $path): array
    {
        // Используем путь относительно проекта
        $phpcsPath = $this->normalizePath(getcwd() . '/vendor/bin/phpcs');
        
        if (!file_exists($phpcsPath)) {
            throw new \RuntimeException('PHP_CodeSniffer не найден. Установите зависимости через composer install.');
        }

        if (!file_exists($path)) {
            throw new \RuntimeException("Путь не существует: $path");
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
            [$path]
        );

        $process = new Process($command);
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

    private function runPHPStan(string $path): array
    {
        // Используем PHP для запуска PHPStan
        $phpstanPath = $this->normalizePath(getcwd() . '/vendor/phpstan/phpstan/bin/phpstan');

        if (!file_exists($phpstanPath)) {
            throw new \RuntimeException('PHPStan не найден. Установите через composer require --dev phpstan/phpstan');
        }

        if (!file_exists($path)) {
            throw new \RuntimeException("Путь не существует: $path");
        }

        $command = [
            PHP_BINARY,
            $phpstanPath,
            'analyse',
            '--error-format=json',
            "--configuration=phpstan.neon",
            $path
        ];

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $exitCode = $process->getExitCode();
            if ($exitCode === 1) {
                $result = json_decode($process->getOutput(), true);
                return $result ?: ['files' => [], 'totals' => ['errors' => 0, 'file_errors' => 0]];
            }
            $errorOutput = $process->getErrorOutput();
            throw new \RuntimeException("PHPStan ошибка: {$errorOutput}");
        }

        return ['files' => [], 'totals' => ['errors' => 0, 'file_errors' => 0]];
    }

    private function normalizePath(string $path): string
    {
        // Заменяем все разделители путей на DIRECTORY_SEPARATOR
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        // Убираем двойные разделители
        $path = preg_replace('#\\' . DIRECTORY_SEPARATOR . '+#', DIRECTORY_SEPARATOR, $path);
        
        // Убираем разделитель в конце пути, если он есть
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        
        return $path;
    }
}