<?php

namespace Fesero\Tahanalyzer\Factory;

use Fesero\Tahanalyzer\Attributes\BinaryPath;
use Fesero\Tahanalyzer\Attributes\ConfigPath;
use Symfony\Component\Process\Process;

abstract class AbstractAnalyzer {
    protected array $exclude;
    protected string $configPath, $path, $root, $binaryPath;

    public function __construct(string $root, string $path, array $exclude = [])
    {
        $this->exclude = $exclude;
        $this->path = $path;
        $this->root = $root;
        $this->binaryPath = $this->getBinaryPath();
        $this->configPath = $this->getConfigPath();
        $this->validatePath();
    }

    abstract public function run(): array;

    protected function normalizePath(string $path): string
    {
        // Заменяем все разделители путей на DIRECTORY_SEPARATOR
        $tempPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        // Убираем двойные разделители
        $tempPath = preg_replace('#\\' . DIRECTORY_SEPARATOR . '+#', DIRECTORY_SEPARATOR, $tempPath);
        
        // Убираем разделитель в конце пути, если он есть
        $tempPath = rtrim($tempPath, DIRECTORY_SEPARATOR);
        
        return $tempPath;
    }

    protected function validatePath(): void
    {
        if (!file_exists($this->path)) {
            throw new \RuntimeException("Путь не существует: $this->path");
        }
    }

    protected function getBinaryPath(): string
    {
        $attributes = $this->getAttributes(BinaryPath::class);

        if (empty($attributes)) {
            throw new \RuntimeException('Путь до бинарного файла не установлен');
        }

        return $this->getFullPath($attributes);
    }

    protected function getConfigPath(): string
    {
        $attributes = $this->getAttributes(ConfigPath::class);

        if (empty($attributes)) {
            throw new \RuntimeException('Путь до конфигурационного файла не установлен');
        }

        return $this->getFullPath($attributes);
    }

    private function getAttributes(string $className): ?\ReflectionAttribute
    {
        $reflection = new \ReflectionClass($this);
        $attributes = $reflection->getAttributes($className);

        return $attributes[0] ?? null;
    }

    private function getFullPath(\ReflectionAttribute $attributes): string
    {
        $config = $attributes->newInstance();
        $fullPath = $this->normalizePath($this->root . $config->path);

        if (!file_exists($fullPath)) {
            throw new \RuntimeException($config->errorMessage);
        }

        return $fullPath;
    }

    protected function createProcess(array $command): Process
    {
        $process = new Process($command);
        $process->setTimeout(300); // Increase timeout to 300 seconds (5 minutes)
        return $process;
    }
}