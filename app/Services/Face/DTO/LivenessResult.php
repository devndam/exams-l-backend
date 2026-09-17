<?php

namespace App\Services\Face\DTO;

class LivenessResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?float $confidence,
        public readonly ?string $referenceImageBinary,
    ) {}
}
