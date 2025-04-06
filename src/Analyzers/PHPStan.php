<?php

namespace Fesero\Tahanalyzer\Analyzers;

use Fesero\Tahanalyzer\Factory\AbstractAnalyzer;
use Fesero\Tahanalyzer\Attributes\BinaryPath;
use Fesero\Tahanalyzer\Attributes\ConfigPath;

#[BinaryPath(
    path: '/vendor/bin/phpstan',
    errorMessage: 'PHPStan не найден. Установите через composer require --dev phpstan/phpstan'
)]
#[ConfigPath(
    path: '/phpstan.neon',
    errorMessage: 'Файл конфигурации phpstan.neon не найден. Запустите composer install для его создания.'
)]
class PHPStan extends AbstractAnalyzer
{
    public function run(): array
    {
        $command = [
            PHP_BINARY,
            $this->binaryPath,
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