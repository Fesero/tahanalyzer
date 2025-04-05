<?php

namespace Fesero\Tahanalyzer;

use \Symfony\Contracts\HttpClient\ResponseInterface;

class CliRunner
{

    private array $availableTests = [
        'sniffer', 'phpstan'
    ];

    public static function run(array $argv)
    {
        //Загрузка конфига
        $config = self::loadConfig(getcwd());

        $endpoint = $config['api']['base_url'] ?? null;
        $token = $config['api']['token'] ?? null;
        $projectName = $config['projectName'] ?? 'Default Project';
        $standart = $config['standart'] ?? null;
        $exclude = $config['exclude'] ?? ['vendor'];
        $paths = $config['paths'] ?? [getcwd()];

        if (!$endpoint || !$token) {
            echo "Ошибка: Не заданы endpoint или token в test-collector.json\n";
            self::showHelp();
            exit(1);
        }

        try {
            $apiClient = new ApiClient(
                $endpoint,
                $token
            );

            foreach ($paths as $path) {
                foreach (self::$availableTests as $testType) {
                    $testAnalyzer = AnalyzerFactory::create(path: $path, type: $testType, exclude: $exclude);
                    $testResult = $testAnalyzer->run();
                    if (!$apiClient->sendResults(data: $testResult, type: $testType, projectName: $projectName)) {
                        echo "❌ " . $apiClient->getFormattedError() . "\n";
                    }
                }
            }
        } catch (\Exception $e) {
            echo "❌ Ошибка: " . $e->getMessage() . "\n";
            echo "Полный трейс:\n" . $e->getTraceAsString();
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