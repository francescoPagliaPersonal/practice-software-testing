<?php
// Copyright (c) 2024-2026 Testsmith. All rights reserved.
// See LICENSE for details.

namespace App\Services\Vault;

use Aws\Kms\KmsClient;
use RuntimeException;

/**
 * AWS KMS direct encrypt/decrypt using a Customer Master Key (CMK).
 *
 * KMS kms:Encrypt accepts up to 4 KB of plaintext — a PAN is 13-19 digits,
 * well within that limit. The CMK never leaves AWS; only ciphertext crosses
 * the network, so the key is never on the application host.
 *
 * Requires: composer require aws/aws-sdk-php
 *
 * IAM permissions needed by the application role:
 *   kms:Encrypt   — on the CMK ARN, by the API service
 *   kms:Decrypt   — on the CMK ARN, by the admin support service only
 *
 * Set in .env:
 *   VAULT_DRIVER=kms
 *   VAULT_KMS_KEY_ID=arn:aws:kms:us-east-1:123456789012:key/mrk-...
 *   AWS_ACCESS_KEY_ID=...
 *   AWS_SECRET_ACCESS_KEY=...
 *   AWS_DEFAULT_REGION=us-east-1
 */
class KmsKeyProvider implements VaultKeyProvider
{
    private KmsClient $client;
    private string $keyId;

    public function __construct(string $keyId, string $region)
    {
        if (empty($keyId)) {
            throw new RuntimeException('VAULT_KMS_KEY_ID must be set when VAULT_DRIVER=kms.');
        }

        // KmsClient picks up AWS credentials from the environment automatically
        // (env vars → IAM instance profile → ~/.aws/credentials).
        $this->client = new KmsClient([
            'version' => 'latest',
            'region'  => $region,
        ]);

        $this->keyId = $keyId;
    }

    /**
     * Calls kms:Encrypt — AWS encrypts the plaintext under the CMK and returns
     * a CiphertextBlob. We base64-encode it for safe DB storage.
     *
     * The CMK ARN is embedded inside the CiphertextBlob by AWS, so decrypt()
     * does not need to pass the key ID again.
     */
    public function encrypt(string $plaintext): string
    {
        $result = $this->client->encrypt([
            'KeyId'     => $this->keyId,
            'Plaintext' => $plaintext,
            // Encryption context binds ciphertext to this application.
            // The same context must be provided to Decrypt.
            'EncryptionContext' => [
                'application' => 'toolshop',
                'field'       => 'pan',
            ],
        ]);

        // CiphertextBlob is raw binary — base64-encode for DB storage.
        return base64_encode((string) $result['CiphertextBlob']);
    }

    /**
     * Calls kms:Decrypt — AWS decrypts the CiphertextBlob and returns plaintext.
     * The key ID is not required; KMS identifies the CMK from the blob.
     */
    public function decrypt(string $stored): string
    {
        $ciphertextBlob = base64_decode($stored, strict: true);
        if ($ciphertextBlob === false) {
            throw new RuntimeException('Invalid ciphertext: base64 decoding failed.');
        }

        $result = $this->client->decrypt([
            'CiphertextBlob' => $ciphertextBlob,
            // Must match the context used during Encrypt or KMS will reject it.
            'EncryptionContext' => [
                'application' => 'toolshop',
                'field'       => 'pan',
            ],
        ]);

        return (string) $result['Plaintext'];
    }
}
