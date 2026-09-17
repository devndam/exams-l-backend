<?php

namespace App\Services\Face\Engines;

use App\Exceptions\ApiException;
use App\Services\Face\Contracts\FaceEngine;
use App\Services\Face\DTO\ComparisonResult;
use App\Services\Face\DTO\DetectionResult;
use App\Services\Face\DTO\EmbeddingResult;
use App\Services\Face\DTO\LivenessResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Generic adapter for an externally hosted face-verification service. Ships as the
 * only concrete FaceEngine — point FACE_SERVICE_URL at whatever service implements
 * this contract:
 *
 *   POST /detect            {image}                 -> {count, descriptors?: float[][]}
 *   POST /embedding         {image}                 -> {embedding: float[], dimension}
 *   POST /compare           {imageA, imageB}        -> {similarity, matched}
 *   POST /liveness/session                          -> {sessionId}
 *   GET  /liveness/credentials                      -> {...}
 *   GET  /liveness/session/{awsSessionId}/result     -> {status, confidence, referenceImage?}
 *
 * All image fields are base64 `data:image/...;base64,...` URIs. A non-2xx response
 * with a JSON {message} body is surfaced as-is so callers can pattern-match messages
 * like "No face detected" the same way the previous engines did.
 */
class HttpFaceEngine implements FaceEngine
{
    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('exams.face.service_url'), '/'))
            ->acceptJson()
            ->timeout(20);
    }

    private function send(\Closure $request): Response
    {
        try {
            $response = $request($this->client());
        } catch (ConnectionException $e) {
            throw new ApiException(502, 'Face service is unreachable. Please try again shortly.');
        }

        $this->assertOk($response);

        return $response;
    }

    public function detectFaces(string $imageBinary): DetectionResult
    {
        $data = $this->send(fn (PendingRequest $c) => $c->post('/detect', ['image' => $this->toDataUri($imageBinary)]))->json();

        return new DetectionResult((int) ($data['count'] ?? 0), $data['descriptors'] ?? []);
    }

    public function getEmbedding(string $imageBinary): EmbeddingResult
    {
        $data = $this->send(fn (PendingRequest $c) => $c->post('/embedding', ['image' => $this->toDataUri($imageBinary)]))->json();

        return new EmbeddingResult($data['embedding'] ?? [], (int) ($data['dimension'] ?? count($data['embedding'] ?? [])));
    }

    public function compareFaces(string $imageABinary, string $imageBBinary): ComparisonResult
    {
        $data = $this->send(fn (PendingRequest $c) => $c->post('/compare', [
            'imageA' => $this->toDataUri($imageABinary),
            'imageB' => $this->toDataUri($imageBBinary),
        ]))->json();

        return new ComparisonResult((float) ($data['similarity'] ?? 0), (bool) ($data['matched'] ?? false));
    }

    public function createLivenessSession(): array
    {
        $data = $this->send(fn (PendingRequest $c) => $c->post('/liveness/session'))->json();

        if (empty($data['sessionId'])) {
            throw new ApiException(502, 'Face service did not return a liveness session id');
        }

        return ['sessionId' => $data['sessionId']];
    }

    public function getLivenessCredentials(): array
    {
        return $this->send(fn (PendingRequest $c) => $c->get('/liveness/credentials'))->json() ?? [];
    }

    public function getLivenessResult(string $awsSessionId): LivenessResult
    {
        $data = $this->send(fn (PendingRequest $c) => $c->get("/liveness/session/{$awsSessionId}/result"))->json();
        $referenceImage = $data['referenceImage'] ?? null;

        return new LivenessResult(
            $data['status'] ?? 'FAILED',
            isset($data['confidence']) ? (float) $data['confidence'] : null,
            $referenceImage ? $this->fromDataUri($referenceImage) : null,
        );
    }

    private function toDataUri(string $imageBinary): string
    {
        return 'data:image/jpeg;base64,'.base64_encode($imageBinary);
    }

    private function fromDataUri(string $dataUri): string
    {
        $b64 = str_contains($dataUri, ',') ? substr($dataUri, strpos($dataUri, ',') + 1) : $dataUri;

        return base64_decode($b64);
    }

    private function assertOk(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('message') ?? "Face service returned HTTP {$response->status()}";

        throw new RuntimeException($message);
    }
}
