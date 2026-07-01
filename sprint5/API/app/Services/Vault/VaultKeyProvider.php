<?php
// Copyright (c) 2024-2026 Testsmith. All rights reserved.
// See LICENSE for details.

namespace App\Services\Vault;

/**
 * Dedicated secure component — the only place that can convert a PAN
 * to ciphertext and back. Implementations must never log plaintext values.
 */
interface VaultKeyProvider
{
    /**
     * Encrypt a plaintext value. Returns a base64-encoded ciphertext string
     * that is safe to store in the database.
     */
    public function encrypt(string $plaintext): string;

    /**
     * Decrypt a ciphertext produced by encrypt(). Returns the original
     * plaintext. Must only be called by explicitly authorized code paths
     * (e.g. admin support endpoints).
     */
    public function decrypt(string $ciphertext): string;
}
