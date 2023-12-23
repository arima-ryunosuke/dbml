<?php

namespace ryunosuke\dbml\Driver;

interface ResultInterface
{
    public function setSameCheckMethod(string $method);

    public function getMetadata(): array;
}
