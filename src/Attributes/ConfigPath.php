<?php
declare(strict_types=1);

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