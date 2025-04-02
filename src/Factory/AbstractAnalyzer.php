<?php

namespace Fesero\Tahanalyzer\Factory;

use Symfony\Component\Process\Process;

abstract class AbstractAnalyzer {
    protected array $exclude;
    protected string $configPath, $path, $root;

    public function __construct(array $exclude = [], string $root, string $path)
    {
        $this->exclude = $exclude;
        $this->configPath = $root . '/phpcs.xml';
        $this->path = $path;
        $this->root = $root;
    }

    abstract public function run(): array;

    protected function normalizePath(): string
    {
        // Заменяем все разделители путей на DIRECTORY_SEPARATOR
        $tempPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->path);
        
        // Убираем двойные разделители
        $tempPath = preg_replace('#\\' . DIRECTORY_SEPARATOR . '+#', DIRECTORY_SEPARATOR, $tempPath);
        
        // Убираем разделитель в конце пути, если он есть
        $tempPath = rtrim($tempPath, DIRECTORY_SEPARATOR);
        
        return $tempPath;
    }

    protected function createProcess(array $command): Process
    {
        return new Process($command);
    }
}