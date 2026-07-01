<?php
// Copyright (c) 2024-2026 Testsmith. All rights reserved.
// See LICENSE for details.

return [

    /*
    |--------------------------------------------------------------------------
    | Vault driver
    |--------------------------------------------------------------------------
    | "local"  — AES-256-GCM from VAULT_KEY env var. No external deps.
    |             Use for local dev and automated tests.
    |
    | "kms"    — AWS KMS direct encrypt/decrypt (kms:Encrypt / kms:Decrypt).
    |             Requires aws/aws-sdk-php and VAULT_KMS_KEY_ID.
    |             Use for staging and production.
    */
    'driver' => env('VAULT_DRIVER', 'local'),

    'local' => [
        // base64-encoded 32-byte random key.
        // Generate: php -r "echo base64_encode(random_bytes(32));"
        'key' => env('VAULT_KEY'),
    ],

    'kms' => [
        // ARN or alias of the KMS Customer Master Key used for PAN encryption.
        // e.g. "arn:aws:kms:us-east-1:123456789012:key/mrk-abc123..."
        //      "alias/toolshop-pan-vault"
        'key_id' => env('VAULT_KMS_KEY_ID'),

        // AWS region where the CMK lives.
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
