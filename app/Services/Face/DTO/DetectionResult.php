<?php

namespace App\Services\Face\DTO;

class DetectionResult
{
    /**
     * @param  array<int, array<float>|null>  $descriptors  Per-face embedding, when the engine returns one inline.
     */
    public function __construct(
        public readonly int $count,
        public readonly array $descriptors = [],
    ) {}
}
