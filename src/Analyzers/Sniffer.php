<?php

namespace Fesero\Tahanalyzer\Analyzers;

use Fesero\Tahanalyzer\Factory\AbstractAnalyzer;
use Fesero\Tahanalyzer\Attributes\BinaryPath;
use Fesero\Tahanalyzer\Attributes\ConfigPath;

#[BinaryPath(
    path: '/vendor/bin/phpcs',
    errorMessage: 'PHP_CodeSniffer не найден. Установите зависимости через composer install.'
)]
#[ConfigPath(
    path: '/phpcs.xml',
    errorMessage: 'Файл конфигурации phpcs.xml не найден. Запустите composer install для его создания.'
)]
class Sniffer extends AbstractAnalyzer
{
    public function run(): array
    {
        $excludeArgs = array_map(fn($dir) => "--ignore={$dir}", $this->exclude);
        $command = array_merge(
            [
                PHP_BINARY,
                $this->binaryPath,
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