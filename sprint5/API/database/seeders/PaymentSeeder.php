<?php
// Copyright (c) 2024-2026 Testsmith. All rights reserved.
// See LICENSE for details.

namespace database\seeders;

use App\Services\Vault\CreditCardVaultService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Credit card seed data is tokenized via CreditCardVaultService so the
     * seeded database matches exactly what a real checkout would produce:
     * masked token in credit_card_number, encrypted blob in pan_ciphertext,
     * and NULL in cvv.
     */
    public function run(): void
    {
        mt_srand(12345); // Fixed seed for reproducibility

        /** @var CreditCardVaultService $vault */
        $vault = app(CreditCardVaultService::class);

        // Tokenize the two seed PANs once so we can reference them below.
        $card1 = $vault->tokenize([
            'credit_card_number' => '0001-0002-0003-0004',
            'expiration_date'    => '02/' . rand(2025, 2028),
            'cvv'                => '000',
            'card_holder_name'   => 'John Doe',
        ]);

        $card2 = $vault->tokenize([
            'credit_card_number' => '1000-2000-3000-4000',
            'expiration_date'    => rand(5, 12) . '/' . rand(2025, 2028),
            'cvv'                => '000',
            'card_holder_name'   => 'Jane Doe',
        ]);

        $paymentMethods = [
            'bank-transfer' => [
                [
                    'bank_name' => 'My Bank',
                    'account_name' => 'John Doe',
                    'account_number' => '987654321',
                    'table' => 'payment_bank_transfer_details',
                    'model' => 'App\\Models\\PaymentBankTransferDetails'
                ],
                [
                    'bank_name' => 'New Bank',
                    'account_name' => 'Jane Doe',
                    'account_number' => '123456789',
                    'table' => 'payment_bank_transfer_details',
                    'model' => 'App\\Models\\PaymentBankTransferDetails'
                ]
            ],
            'credit-card' => [
                array_merge($card1, [
                    'table' => 'payment_credit_card_details',
                    'model' => 'App\\Models\\PaymentCreditCardDetails'
                ]),
                array_merge($card2, [
                    'table' => 'payment_credit_card_details',
                    'model' => 'App\\Models\\PaymentCreditCardDetails'
                ]),
            ],
            'gift-card' => [
                [
                    'gift_card_number' => Str::random(16),
                    'validation_code' => Str::random(4),
                    'table' => 'payment_gift_card_details',
                    'model' => 'App\\Models\\PaymentGiftCardDetails'
                ]
            ],
            'cash-on-delivery' => [
                [
                    'table' => 'payment_cash_on_delivery_details',
                    'model' => 'App\\Models\\PaymentCashOnDeliveryDetails'
                ]
            ],
            'buy-now-pay-later' => [
                [
                    'monthly_installments' => 6,
                    'table' => 'payment_bnpl_details',
                    'model' => 'App\\Models\\PaymentBnplDetails'
                ],
                [
                    'monthly_installments' => 3,
                    'table' => 'payment_bnpl_details',
                    'model' => 'App\\Models\\PaymentBnplDetails'
                ]
            ]
        ];

        $invoices = DB::table('invoices')->get();

        foreach ($invoices as $key => $invoice) {
            $paymentTypeKeys = array_keys($paymentMethods);
            $paymentType = $paymentTypeKeys[$key % count($paymentTypeKeys)];
            $paymentDetails = $paymentMethods[$paymentType][$key % count($paymentMethods[$paymentType])];

            $detailsToInsert = $paymentDetails;
            unset($detailsToInsert['table']);
            unset($detailsToInsert['model']);

            $paymentDetailsId = Str::ulid()->toBase32();
            DB::table($paymentDetails['table'])->insert([array_merge($detailsToInsert, ['id' => $paymentDetailsId])]);

            DB::table('payments')->insert([[
                'id' => Str::ulid()->toBase32(),
                'invoice_id' => $invoice->id,
                'payment_method' => $paymentType,
                'payment_details_id' => $paymentDetailsId,
                'payment_details_type' => $paymentDetails['model']
            ]]);

            DB::table('invoices')->where('id', $invoice->id)->update(['status' => 'AWAITING_FULFILLMENT']);
        }
    }
}
