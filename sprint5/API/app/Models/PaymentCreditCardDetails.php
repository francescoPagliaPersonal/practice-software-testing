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
    protected $hidden = ['id', 'created_at', 'updated_at', 'pan_ciphertext'];

    public function payment()
    {
        return $this->morphOne(Payment::class, 'payment_details');
    }
}
