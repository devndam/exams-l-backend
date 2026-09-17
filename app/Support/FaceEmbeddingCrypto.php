<?php

namespace App\Support;

use RuntimeException;

/**
 * AES-256-GCM port of the Node engine's `encryption.js`. Embeddings are packed as
 * IEEE-754 little-endian doubles (`pack('e*', ...)`) — an explicit byte order, unlike
 * the Node original which relied on `Float64Array`'s host-endianness layout.
 */
class FaceEmbeddingCrypto
{
    private const CIPHER = 'aes-256-gcm';

    /**
     * @param  array<float>  $embedding
     * @return array{encrypted: string, iv: string, authTag: string}
     */
    public function encrypt(array $embedding): array
    {
        $key = $this->key();
        $iv = random_bytes(16);

        $buffer = pack('e*', ...$embedding);

        $tag = '';
        $encrypted = openssl_encrypt($buffer, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($encrypted === false) {
            throw new RuntimeException('Failed to encrypt face embedding');
        }

        return [
            'encrypted' => $encrypted,
            'iv' => bin2hex($iv),
            'authTag' => bin2hex($tag),
        ];
    }

    /**
     * @return array<float>
     */
    public function decrypt(?string $encrypted, ?string $ivHex, ?string $authTagHex): array
    {
        if (! $encrypted || ! $ivHex || ! $authTagHex) {
            throw new RuntimeException('decryptEmbedding: missing encrypted, iv, or authTag — re-enrollment required');
        }

        $key = $this->key();
        $iv = hex2bin($ivHex);
        $authTag = hex2bin($authTagHex);

        $decrypted = openssl_decrypt($encrypted, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $authTag);

        if ($decrypted === false) {
            throw new RuntimeException('decryptEmbedding: authentication failed — data is tampered or corrupt');
        }

        if ($decrypted === '' || strlen($decrypted) % 8 !== 0) {
            throw new RuntimeException('decryptEmbedding: decrypted length '.strlen($decrypted).' is not a multiple of 8');
        }

        $floats = array_values(unpack('e*', $decrypted));

        foreach ($floats as $i => $value) {
            if (! is_finite($value)) {
                throw new RuntimeException("decryptEmbedding: value at index {$i} is not finite — embedding is corrupt");
            }
        }

        return $floats;
    }

    private function key(): string
    {
        $key = hex2bin((string) config('exams.face.encryption_key'));

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('FACE_ENCRYPTION_KEY must be 64 hex chars (32 bytes)');
        }

        return $key;
    }
}
