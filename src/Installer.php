<?php

namespace Fesero\Tahanalyzer;

class Installer {
    public static function copyConfig() {
        self::updateConfig(
            'test-collector.example.json',
            'test-collector.json'
        );
        
        self::updateConfig(
            'phpstan.example.neon',
            'phpstan.neon',
            isJson: false
        );

        self::updateConfig(
            'phpcs.example.xml',
            'phpcs.xml',
            isJson: false
        );
    }

    private static function updateConfig(
        string $sourceFile,
        string $destinationFile,
        bool $isJson = true
    ) {
        $sourcePath = __DIR__ . "/../config/{$sourceFile}";
        $destinationPath = getcwd() . "/{$destinationFile}";

        // Если файла нет — просто копируем
        if (!file_exists($destinationPath)) {
            copy($sourcePath, $destinationPath);
            echo "Файл {$destinationFile} создан.\n";
            return;
        }

        // Сравниваем версии
        $sourceVersion = self::getConfigVersion($sourcePath, $isJson);
        $destinationVersion = self::getConfigVersion($destinationPath, $isJson);

        if ($sourceVersion > $destinationVersion) {
            echo "Обнаружено обновление конфига {$destinationFile} (v{$destinationVersion} → v{$sourceVersion})\n";
            
            // Создаем бэкап
            $backupPath = $destinationPath . '.bak';
            rename($destinationPath, $backupPath);
            echo "Бэкап сохранен: {$backupPath}\n";

            // Сливаем конфиги
            $mergedConfig = self::mergeConfigs(
                $sourcePath,
                $backupPath,
                $isJson
            );

            // Сохраняем новый конфиг
            file_put_contents($destinationPath, $mergedConfig);
            echo "Конфиг {$destinationFile} обновлен с сохранением ваших настроек.\n";
        }
    }

    private static function getConfigVersion(string $path, bool $isJson): int {
        $content = file_get_contents($path);
        if ($isJson) {
            $config = json_decode($content, true);
            return $config['version'] ?? 1;
        }
        
        // Для XML и YAML
        if (str_ends_with($path, '.xml')) {
            preg_match('/version"\s+value="(\d+)"/', $content, $matches);
            return $matches[1] ?? 1;
        }
        
        // Для YAML (phpstan.neon)
        preg_match('/version:\s*(\d+)/', $content, $matches);
        return $matches[1] ?? 1;
    }

    private static function mergeConfigs(
        string $newConfigPath,
        string $oldConfigPath,
        bool $isJson
    ): string {
        if ($isJson) {
            $newConfig = json_decode(file_get_contents($newConfigPath), true);
            $oldConfig = json_decode(file_get_contents($oldConfigPath), true);
            
            // Сливаем, сохраняя пользовательские настройки
            $merged = array_merge($newConfig, $oldConfig);
            unset($merged['version']); // Удаляем версию для замены
            $merged['version'] = $newConfig['version'];
            
            return json_encode($merged, JSON_PRETTY_PRINT);
        }

        // Для XML и YAML (пока простая замена)
        return file_get_contents($newConfigPath);
    }
}