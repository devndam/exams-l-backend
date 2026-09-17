<?php

namespace App\Services\Face\DTO;

class EmbeddingResult
{
    /**
     * @param  array<float>  $embedding
     */
    public function __construct(
        public readonly array $embedding,
        public readonly int $dimension,
    ) {}
}
