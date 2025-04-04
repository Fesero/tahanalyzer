<?php

namespace Fesero\Tahanalyzer\Attributes;

class BinaryPath
{
    public function __construct(
        public string $path,
        public string $errorMessage
    ) {}
}