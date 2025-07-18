<?php

namespace ryunosuke\dbml\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Scope extends AbstractAttribute
{
    public function __construct(
        bool $selective = true,
        bool $affective = true,
    ) {
    }
}
