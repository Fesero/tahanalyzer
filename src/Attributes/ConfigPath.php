<?php

namespace Fesero\Tahanalyzer\Attributes;

class ConfigPath
{
    public function __construct(
        public string $path,
        public string $errorMessage
    ) {}
}