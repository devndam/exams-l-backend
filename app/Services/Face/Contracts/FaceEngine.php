<?php

namespace App\Services\Face\Contracts;

use App\Services\Face\DTO\ComparisonResult;
use App\Services\Face\DTO\DetectionResult;
use App\Services\Face\DTO\EmbeddingResult;
use App\Services\Face\DTO\LivenessResult;

interface FaceEngine
{
    public function detectFaces(string $imageBinary): DetectionResult;

    public function getEmbedding(string $imageBinary): EmbeddingResult;

    public function compareFaces(string $imageABinary, string $imageBBinary): ComparisonResult;

    /** @return array{sessionId: string} */
    public function createLivenessSession(): array;

    /** @return array<string, mixed> */
    public function getLivenessCredentials(): array;

    public function getLivenessResult(string $awsSessionId): LivenessResult;
}
