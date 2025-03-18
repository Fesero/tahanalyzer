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
        // Используем путь относительно текущего пакета
        $phpcsPath = __DIR__ . '/../../../bin/phpcs';
        
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
                return json_decode($process->getOutput(), true);
            }
            $errorOutput = $process->getErrorOutput();
            $output = $process->getOutput();
            throw new \RuntimeException("Ошибка анализа (код $exitCode): $errorOutput\n$output");
        }

        return json_decode($process->getOutput(), true) ?: [];
    }

    private function runPHPStan(string $path): array
    {
        $phpstanPath = __DIR__ . '/../../../bin/phpstan';

        if (!file_exists($phpstanPath)) {
            throw new \RuntimeException('PHPStan не найден. Установите через composer require --dev phpstan/phpstan');
        }

        $command = [
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
                return json_decode($process->getOutput(), true);
            }
            $errorOutput = $process->getErrorOutput();
            throw new \RuntimeException("PHPStan ошибка: {$errorOutput}");
        }

        return json_decode($process->getOutput(), true) ?: [];
    }
}