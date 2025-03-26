<?php

namespace Fesero\Tahanalyzer;

use \Symfony\Contracts\HttpClient\ResponseInterface;

class CliRunner
{
    public static function run(array $argv)
    {
        //Загрузка конфига
        $config = self::loadConfig(getcwd());

        $endpoint = $config['api']['base_url'];
        $token = $config['api']['token'];
        $standart = $config['standart'];
        $exclude = $config['exclude'];
        $paths = $config['paths'];

        if (!$endpoint || !$token) {
            self::showHelp();
            exit(1);
        }

        try {
            $analyzer = new Analyzer(
                $exclude ?? ['vendor']
            );
            $apiClient = new ApiClient(
                $endpoint ?? '',
                $token ?? ''
            );

            foreach ($config['paths'] as $path) {
                // Запуск PHP_CodeSniffer
                $snifferResults = $analyzer->runAnalyze($path, 'sniffer');
                if (!$apiClient->sendResults($snifferResults, 'sniffer')) {
                    echo "❌ " . $apiClient->getFormattedError() . "\n";
                }
    
                // Запуск PHPStan
                $phpstanResults = $analyzer->runAnalyze($path, 'phpstan');
                if (!$apiClient->sendResults($phpstanResults, 'phpstan')) {
                    echo "❌ " . $apiClient->getFormattedError() . "\n";
                }
            }
        } catch (\Exception $e) {
            echo "❌ Ошибка: " . $e->getMessage() . "\n";
            echo "Полный трейс:\n" . $e->getTraceAsString(); // Добавьте эту строку
            exit(1);
        }
    }

    private static function handleError(?ResponseInterface $response, ?string $error = null) {
        if ($response === null) {
            echo "❌ Ошибка соединения: " . $error . "\n";
            return;
        }

        try {
            $statusCode = $response->getStatusCode();
            echo "❌ Ошибка сервера (HTTP {$statusCode}):\n";
            
            try {
                $content = $response->getContent();
                $data = json_decode($content, true);
                
                if (isset($data['message'])) {
                    echo "Сообщение: {$data['message']}\n";
                } elseif (isset($data['error'])) {
                    echo "Сообщение: {$data['error']}\n";
                } else {
                    echo "Детали:\n";
                    print_r($data);
                }
            } catch (\Exception $e) {
                echo "Не удалось получить содержимое ответа\n";
            }
        } catch (\Exception $e) {
            echo "❌ Ошибка при обработке ответа: " . $e->getMessage() . "\n";
            echo "Статус: HTTP {$response->getStatusCode()}\n";
        }
    }

    private static function loadConfig($path)
    {
        $configPath = $path . '/test-collector.json';

        if (!file_exists($configPath)) {
            throw new \RuntimeException('Конфиг test-collector.json не найден');
        }

        return json_decode(file_get_contents($configPath), true);
    }

    private static function showHelp()
    {
        echo <<<HELP
Использование: test-collector --endpoint=URL --token=TOKEN [--path=PATH]
  --endpoint  URL бэкенда
  --token     API-токен
  --path      Путь для анализа (по умолчанию: текущая директория)
HELP;
    }
}