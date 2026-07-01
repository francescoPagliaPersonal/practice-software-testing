<?php
// Copyright (c) 2024-2026 Testsmith. All rights reserved.
// See LICENSE for details.

namespace App\Services\Vault;

use RuntimeException;

/**
 * AES-256-GCM encryption using a 32-byte key stored in VAULT_KEY env var.
 *
 * Use for local development and automated tests. In production, replace
 * with KmsKeyProvider so the key never touches the application host.
 *
 * The ciphertext format (all base64-encoded, pipe-delimited) is:
 *   base64(iv) | base64(tag) | base64(ciphertext)
 */
class LocalKeyProvider implements VaultKeyProvider
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;   // 96-bit IV — GCM standard
    private const TAG_LENGTH = 16;  // 128-bit authentication tag

    private string $key;

    public function __construct(string $base64Key)
    {
        $decoded = base64_decode($base64Key, strict: true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException(
                'VAULT_KEY must be a base64-encoded 32-byte value. ' .
                'Generate one with: php -r "echo base64_encode(random_bytes(32));"'
            );
        }
        $this->key = $decoded;
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new RuntimeException('AES-256-GCM encryption failed: ' . openssl_error_string());
        }

        // Store iv + tag + ciphertext so decrypt() is self-contained.
        return base64_encode($iv) . '|' . base64_encode($tag) . '|' . base64_encode($ciphertext);
    }

    public function decrypt(string $stored): string
    {
        $parts = explode('|', $stored);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid ciphertext format.');
        }

        [$iv, $tag, $ciphertext] = array_map('base64_decode', $parts);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed — ciphertext may be tampered or key is wrong.');
        }

        return $plaintext;
    }
}
