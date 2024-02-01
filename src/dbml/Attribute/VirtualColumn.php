<?php

namespace ryunosuke\dbml\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class VirtualColumn extends AbstractAttribute
{
    public function __construct(
        ?string $type = null,
        bool $lazy = true,
        bool $implicit = false
    ) {
    }
}
