<?php
// Copyright (c) 2024-2026 Testsmith. All rights reserved.
// See LICENSE for details.

namespace App\Providers;

use App\Services\Vault\KmsKeyProvider;
use App\Services\Vault\LocalKeyProvider;
use App\Services\Vault\VaultKeyProvider;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

/**
 * Wires the VaultKeyProvider implementation selected by VAULT_DRIVER.
 *
 * The CreditCardVaultService depends on VaultKeyProvider by interface, so
 * swapping drivers (local ↔ kms) requires only a change to VAULT_DRIVER in
 * the environment — no application code changes.
 */
class VaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VaultKeyProvider::class, function ($app) {
            $config = $app['config']['vault'];
            $driver = $config['driver'] ?? 'local';

            return match ($driver) {
                'local' => new LocalKeyProvider(
                    base64Key: $config['local']['key']
                        ?? throw new InvalidArgumentException('VAULT_KEY must be set when VAULT_DRIVER=local.')
                ),
                'kms' => new KmsKeyProvider(
                    keyId:  $config['kms']['key_id']
                        ?? throw new InvalidArgumentException('VAULT_KMS_KEY_ID must be set when VAULT_DRIVER=kms.'),
                    region: $config['kms']['region'] ?? 'us-east-1'
                ),
                default => throw new InvalidArgumentException("Unknown vault driver: {$driver}"),
            };
        });
    }
}
