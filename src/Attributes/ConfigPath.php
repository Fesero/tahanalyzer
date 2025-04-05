<?php

namespace Fesero\Tahanalyzer\Attributes;

use Attribute;

#[Attribute()]
class ConfigPath
{
    public function __construct(
        public string $path,
        public string $errorMessage
    ) {}
}