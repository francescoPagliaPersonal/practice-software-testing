<?php
// Copyright (c) 2024-2026 Testsmith. All rights reserved.
// See LICENSE for details.

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentCreditCardDetails extends BaseModel
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    // pan_ciphertext is excluded from all API responses — it is only
    // accessible via CreditCardVaultService::detokenize() in admin endpoints.
    // cvv and pan_ciphertext must never appear in API responses.
    // cvv: PCI-DSS req 3.2 — must not be stored or returned.
    // pan_ciphertext: raw ciphertext is admin-only via detokenize().
    protected $hidden = ['id', 'created_at', 'updated_at', 'cvv', 'pan_ciphertext'];

    public function payment()
    {
        return $this->morphOne(Payment::class, 'payment_details');
    }
}
