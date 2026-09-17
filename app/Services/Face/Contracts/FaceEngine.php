<?php

namespace App\Services\Face\Contracts;

use App\Services\Face\DTO\ComparisonResult;
use App\Services\Face\DTO\LivenessResult;

interface FaceEngine
{
    public function compareFaces(string $imageABinary, string $imageBBinary): ComparisonResult;

    /** @return array{sessionId: string} */
    public function createLivenessSession(): array;

    /** @return array<string, mixed> */
    public function getLivenessCredentials(): array;

    public function getLivenessResult(string $awsSessionId): LivenessResult;
}
