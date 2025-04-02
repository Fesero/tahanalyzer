<?php

namespace Fesero\Tahanalyzer\Analyzers;

use Fesero\Tahanalyzer\Factory\AbstractAnalyzer;

class PHPStan extends AbstractAnalyzer
{
    public function run(): array
    {
        // Используем PHP для запуска PHPStan
        $phpstanPath = $this->normalizePath($this->root . '/vendor/phpstan/phpstan/bin/phpstan');

        if (!file_exists($phpstanPath)) {
            throw new \RuntimeException('PHPStan не найден. Установите через composer require --dev phpstan/phpstan');
        }

        if (!file_exists($this->path)) {
            throw new \RuntimeException("Путь не существует: $this->path");
        }

        $command = [
            PHP_BINARY,
            $phpstanPath,
            'analyse',
            '--error-format=json',
            "--configuration=phpstan.neon",
            $this->path
        ];

        $process = $this->createProcess($command);
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
}