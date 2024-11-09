<?php

namespace ryunosuke\dbml\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER)]
class AssumeType extends AbstractAttribute
{
    private array $types;

    public function __construct(string ...$types)
    {
        $this->types = $types;
    }

    public function type(array $typeMap): string
    {
        return implode('|', array_map(fn($v) => $typeMap[$v], $this->types));
    }
}
