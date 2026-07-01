<?php
// Copyright (c) 2024-2026 Testsmith. All rights reserved.
// See LICENSE for details.

namespace App\Services\Vault;

/**
 * Dedicated secure component for PCI-DSS pseudonymization of card data.
 *
 * This is the single authorised entry point for all PAN handling.
 * Nothing outside this class may read or write raw PANs — enforced by
 * never passing the raw PAN through InvoiceService or any model field
 * other than pan_ciphertext.
 *
 * PCI-DSS rules applied here:
 *   - CVV/CVC is stripped immediately; it is never stored (not even encrypted).
 *   - Full PAN is encrypted via the injected VaultKeyProvider (KMS or local).
 *   - Only the last-4 masked token (****-****-****-XXXX) is stored in the
 *     credit_card_number column for support/display purposes.
 */
class CreditCardVaultService
{
    public function __construct(private readonly VaultKeyProvider $keyProvider)
    {
    }

    /**
     * Convert raw card input into a storage-safe payload.
     *
     * @param  array{
     *     credit_card_number: string,
     *     expiration_date: string,
     *     cvv: string,
     *     card_holder_name: string
     * } $rawDetails  The card fields exactly as received from the request.
     *
     * @return array{
     *     credit_card_number: string,  masked token  ****-****-****-XXXX
     *     expiration_date: string,
     *     cvv: null,                   never stored
     *     card_holder_name: string,
     *     pan_ciphertext: string       KMS/AES-256-GCM blob
     * }
     */
    public function tokenize(array $rawDetails): array
    {
        $pan = $rawDetails['credit_card_number'];

        // Strip any separators to get the raw digit string.
        $digits = preg_replace('/\D/', '', $pan);
        $last4  = substr($digits, -4);

        return [
            // Display token — safe to store and return to the UI.
            'credit_card_number' => '****-****-****-' . $last4,
            'expiration_date'    => $rawDetails['expiration_date'],
            'card_holder_name'   => $rawDetails['card_holder_name'],

            // CVV MUST NOT be stored — PCI-DSS 3.2.1 / requirement 3.2.
            'cvv'                => null,

            // Reversible ciphertext — only accessible via detokenize() by
            // authorised admin endpoints.
            'pan_ciphertext'     => $this->keyProvider->encrypt($pan),
        ];
    }

    /**
     * Recover the original PAN from a stored ciphertext.
     * Must only be called from explicitly authorised admin code paths.
     */
    public function detokenize(string $panCiphertext): string
    {
        return $this->keyProvider->decrypt($panCiphertext);
    }
}
