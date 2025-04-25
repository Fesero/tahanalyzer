<?php
declare(strict_types=1);

namespace Fesero\Tahanalyzer\Factory;

use Fesero\Tahanalyzer\Attributes\BinaryPath;
use Fesero\Tahanalyzer\Attributes\ConfigPath;
use Symfony\Component\Process\Process;

abstract class AbstractAnalyzer 
{
    protected string $configPath, $binaryPath;

    /**
     * Summary of __construct
     * @param string $root
     * @param string $path
     * @param array $exclude
     */
    public function __construct(
        protected string $root, 
        protected string $path, 
        protected array $exclude = []
        )
    {
        $this->binaryPath = $this->getBinaryPath();
        $this->configPath = $this->getConfigPath();
        $this->validatePath();
    }

    /**
     * Summary of run
     * @return void
     */
    abstract public function run(): array;

    /**
     * Summary of normalizePath
     * @param string $path
     * @return string
     */
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

    /**
     * Summary of validatePath
     * @throws \RuntimeException
     * @return void
     */
    protected function validatePath(): void
    {
        if (!file_exists($this->path)) {
            throw new \RuntimeException("Путь не существует: $this->path");
        }
    }

    /**
     * Summary of getBinaryPath
     * @throws \RuntimeException
     * @return string
     */
    protected function getBinaryPath(): string
    {
        $attributes = $this->getAttributes(BinaryPath::class);

        if (empty($attributes)) {
            throw new \RuntimeException('Путь до бинарного файла не установлен');
        }

        return $this->getFullPath($attributes);
    }

    /**
     * Summary of getConfigPath
     * @throws \RuntimeException
     * @return string
     */
    protected function getConfigPath(): string
    {
        $attributes = $this->getAttributes(ConfigPath::class);

        if (empty($attributes)) {
            throw new \RuntimeException('Путь до конфигурационного файла не установлен');
        }

        return $this->getFullPath($attributes);
    }

    /**
     * Summary of getAttributes
     * @param string $className
     * @return mixed|\ReflectionAttribute|null
     */
    private function getAttributes(string $className): ?\ReflectionAttribute
    {
        $reflection = new \ReflectionClass($this);
        $attributes = $reflection->getAttributes($className);

        return $attributes[0] ?? null;
    }

    /**
     * Summary of getFullPath
     * @param \ReflectionAttribute $attributes
     * @throws \RuntimeException
     * @return string
     */
    private function getFullPath(\ReflectionAttribute $attributes): string
    {
        $config = $attributes->newInstance();
        $fullPath = $this->normalizePath($this->root . $config->path);

        if (!file_exists($fullPath)) {
            throw new \RuntimeException($config->errorMessage);
        }

        return $fullPath;
    }

    /**
     * Summary of createProcess
     * @param array $command
     * @return Process
     */
    protected function createProcess(array $command): Process
    {
        $process = new Process($command);
        $process->setTimeout(300); // Increase timeout to 300 seconds (5 minutes)
        return $process;
    }
}
