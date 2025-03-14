<?php

namespace Fesero\Tahanalyzer;

class CliRunner
{
    public static function run(array $argv)
    {
        //Загрузка конфига
        $config = self::loadConfig(getcwd());

        $endpoint = $config['api']['endpoint'];
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
                $standart ?? 'PSR2',
                $exclude ?? ['vendor']
            );
            $apiClient = new ApiClient(
                $endpoint ?? '',
                $token ?? ''
            );

            foreach ($paths as $path) {
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
            }
        } catch (\Exception $e) {
            echo "❌ Ошибка: " . $e->getMessage() . "\n";
            echo "Полный трейс:\n" . $e->getTraceAsString(); // Добавьте эту строку
            exit(1);
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