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
    }
}