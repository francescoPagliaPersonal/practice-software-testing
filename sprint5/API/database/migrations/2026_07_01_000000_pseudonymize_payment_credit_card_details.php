<?php
// Copyright (c) 2024-2026 Testsmith. All rights reserved.
// See LICENSE for details.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * US9300 — PCI-DSS pseudonymization of stored credit card data.
 *
 * Changes:
 *   - pan_ciphertext (TEXT, nullable) — KMS/AES-256-GCM encrypted PAN.
 *   - cvv becomes nullable — PCI-DSS 3.2 forbids storing CVV post-auth;
 *     the application always stores NULL.
 *   - credit_card_number already fits the masked token (****-****-****-XXXX)
 *     in its existing varchar(40) column; no schema change needed there.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_credit_card_details', function (Blueprint $table) {
            // Encrypted PAN blob. TEXT because KMS ciphertexts are base64
            // strings of variable length (~380 chars for a 16-digit PAN).
            $table->text('pan_ciphertext')->nullable()->after('credit_card_number');

            // CVV must never be stored after authorization (PCI-DSS req 3.2).
            $table->string('cvv', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_credit_card_details', function (Blueprint $table) {
            $table->dropColumn('pan_ciphertext');
            $table->string('cvv', 10)->nullable(false)->change();
        });
    }
};
