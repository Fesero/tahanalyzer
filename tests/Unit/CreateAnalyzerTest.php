<?php

use Fesero\Tahanalyzer\AnalyzerFactory;
use Fesero\Tahanalyzer\Analyzers\Sniffer;

test('createAnalyzer', function() {
    $analyzer = AnalyzerFactory::create('/test', 'sniffer');

    expect($analyzer)->toBeClass(Sniffer::class);
});