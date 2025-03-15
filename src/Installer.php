<?php

namespace Fesero\Tahanalyzer;

class Installer
{
    public static function copyConfig()
    {
        $source = __DIR__ . '/../config/test-collector.example.json';
        $destination = getcwd() . '/test-collector.json';

        if (!file_exists($destination)) {
            copy($source, $destination);
            echo "\nФайл конфигурации создан: test-collector.json\n";
        }

        $sourcePHPStan = __DIR__ . '/../config/phpstan.neon';
        $destinationPHPStan = getcwd() . '/phpstan.neon';

        if (!file_exists($destinationPHPStan)) {
            copy($sourcePHPStan, $destinationPHPStan);
            echo "\nФайл конфигурации создан: phpstan.neon\n";
        }
    }
}