<?php
declare(strict_types=1);

namespace Fesero\Tahanalyzer;

use Fesero\Tahanalyzer\Factory\AbstractAnalyzer;
use Fesero\Tahanalyzer\Analyzers;

class AnalyzerFactory
{
    /**
     * Summary of create
     * @param string $path
     * @param string $type
     * @param array $exclude
     * @return Analyzers\PHPStan|Analyzers\Sniffer
     */
    public static function create(string $path, string $type, array $exclude = []): AbstractAnalyzer
    {
        return match($type) {
            'sniffer' => new Analyzers\Sniffer(exclude: $exclude, root: getcwd(), path: $path),
            'phpstan' => new Analyzers\PHPStan(exclude: $exclude, root: getcwd(), path: $path)
        };
    }
}
