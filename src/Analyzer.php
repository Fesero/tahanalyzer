<?php

namespace Fesero\Tahanalyzer;

use Symfony\Component\Process\Process;

class Analyzer
{
    public function runAnalysis(string $path): array
    {
        $process = new Process(['phpcs', '--report=json', $path]);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Analysis failed: ' . $process->getErrorOutput());
        }

        return json_decode($process->getOutput(), true) ?: [];
    }
}