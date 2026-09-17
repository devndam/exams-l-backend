<?php

namespace App\Services\Face\DTO;

class ComparisonResult
{
    public function __construct(
        public readonly float $similarity,
        public readonly bool $matched,
    ) {}
}
