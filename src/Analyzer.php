<?php

namespace Fesero\Tahanalyzer;

use Symfony\Component\Process\Process;

class Analyzer
{
    private $standart;
    private $exclude;

    public function __construct(string $standart = 'PSR2', array $exclude = [])
    {
        $this->standart = $standart;
        $this->exclude = $exclude;
    }

    public function runAnalyze(string $path, string $type): array
    {
        return match($type) {
            'Sniffer' => $this->runSniffer($path),
            'PHPStan' => $this->runPHPStan($path)
        };
    }

    private function runSniffer(string $path): array
    {
        // Используем путь относительно текущего пакета
        $phpcsPath = __DIR__ . '/../../../squizlabs/php_codesniffer/bin/phpcs';
        
        if (!file_exists($phpcsPath)) {
            throw new \RuntimeException('PHP_CodeSniffer не найден. Установите зависимости через composer install.');
        }

        if (!file_exists($path)) {
            throw new \RuntimeException("Путь не существует: $path");
        }

        $excludeArgs = array_map(fn($dir) => "--ignore={$dir}", $this->exclude);
        $command = array_merge(
            [$phpcsPath, '--report=json', "--standard={$this->standart}"],
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
        $phpstanPath = __DIR__ . '/../../../phpstan/phpstan';

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
            $errorOutput = $process->getErrorOutput();
            throw new \RuntimeException("PHPStan ошибка: {$errorOutput}");
        }

        return json_decode($process->getOutput(), true) ?: [];
    }
}