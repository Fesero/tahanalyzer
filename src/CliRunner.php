<?php

namespace Fesero\Tahanalyzer;

class CliRunner
{
    public static function run(array $argv)
    {
        // Парсинг аргументов
        $opts = getopt('', ['endpoint:', 'token:', 'path:']);
        $path = $opts['path'] ?? getcwd();
        $endpoint = $opts['endpoint'] ?? $_ENV['TEST_COLLECTOR_ENDPOINT'] ?? '';
        $token = $opts['token'] ?? $_ENV['TEST_COLLECTOR_TOKEN'] ?? '';

        if (!$endpoint || !$token) {
            self::showHelp();
            exit(1);
        }

        try {
            $analyzer = new Analyzer();
            $apiClient = new ApiClient($endpoint, $token);
            $results = $analyzer->runAnalysis($path);

            if ($apiClient->sendResults($results)) {
                echo "✅ Результаты успешно отправлены!\n";
            } else {
                $response = $apiClient->getLastResponse();
                if ($response instanceof \Symfony\Contracts\HttpClient\ResponseInterface) {
                    $content = $response->getContent();
                    echo "❌ Ошибка: " . $response->getStatusCode() . " " . $content . "\n";
                }
            }
        } catch (\Exception $e) {
            echo "❌ Ошибка: " . $e->getMessage() . "\n";
            echo "Полный трейс:\n" . $e->getTraceAsString(); // Добавьте эту строку
            exit(1);
        }
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