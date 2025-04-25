<?php
declare(strict_types=1);
use Fesero\Tahanalyzer\AnalyzerFactory;

it("createAnalyzer", function (): void {
    expect(fn() => AnalyzerFactory::create(path: "/", type: "sniffer"))
    ->toThrow(RuntimeException::class);
});
