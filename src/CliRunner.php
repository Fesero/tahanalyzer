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
                $standart ?? 'PSR2',
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
                    self::handleError($apiClient->getLastResponse());
                }
    
                // Запуск PHPStan
                $phpstanResults = $analyzer->runAnalyze($path, 'phpstan');
                if (!$apiClient->sendResults($phpstanResults, 'phpstan')) {
                    self::handleError($apiClient->getLastResponse());
                }
            }
        } catch (\Exception $e) {
            echo "❌ Ошибка: " . $e->getMessage() . "\n";
            echo "Полный трейс:\n" . $e->getTraceAsString(); // Добавьте эту строку
            exit(1);
        }
    }

    private static function handleError(ResponseInterface $response) {
        $content = json_decode($response->getContent(), true);
        echo "❌ Ошибка: " . $response->getStatusCode() . "\n";
        print_r($content['errors']);
        exit(1);
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