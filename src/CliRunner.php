<?php
declare(strict_types=1);

namespace Fesero\Tahanalyzer;

use Fesero\Tahanalyzer\AnalyzerFactory;

class CliRunner
{
    private static array $availableTests = [
        'sniffer' => 'sniffer',
        'phpstan' => 'static_analysis'
    ];

    /**
     * Summary of run
     * @param array $argv
     * @return void
     */
    public static function run(array $argv): void
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
                foreach (self::$availableTests as $testType => $routePath) {
                    $testAnalyzer = AnalyzerFactory::create(path: $path, type: $testType, exclude: $exclude);
                    $testResult = $testAnalyzer->run();

                    $apiClient->sendResults(data: $testResult, type: $routePath, projectName: $projectName);

                    $lastResponse = $apiClient->getLastResponse();
                    if ($lastResponse && $lastResponse->getStatusCode() >= 400) {
                        echo "❌ Ошибка при отправке результатов ({$testType} для {$path}): " . $apiClient->getFormattedError() . "\n";
                    } else if (!$lastResponse && $apiClient->getLastError()){
                         echo "❌ Ошибка соединения: " . $apiClient->getLastError() . "\n";
                    }
                }
            }
        } catch (\Exception $e) {
            echo "❌ Ошибка: " . $e->getMessage() . "\n";
            echo "Полный трейс:\n" . $e->getTraceAsString();
            exit(1);
        }
    }

    /**
     * Summary of loadConfig
     * @param mixed $path
     * @throws \RuntimeException
     * @return mixed
     */
    private static function loadConfig($path): mixed
    {
        $configPath = $path . '/test-collector.json';

        if (!file_exists($configPath)) {
            throw new \RuntimeException('Конфиг test-collector.json не найден');
        }

        return json_decode(file_get_contents($configPath), true);
    }

    /**
     * Summary of showHelp
     * @return void
     */
    private static function showHelp(): void
    {
        echo <<<HELP
Использование: test-collector --endpoint=URL --token=TOKEN [--path=PATH]
  --endpoint  URL бэкенда
  --token     API-токен
  --path      Путь для анализа (по умолчанию: текущая директория)
HELP;
    }
}