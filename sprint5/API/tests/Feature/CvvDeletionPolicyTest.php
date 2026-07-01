<?php
// Copyright (c) 2024-2026 Testsmith. All rights reserved.
// See LICENSE for details.

// US9300 — Task 9300-2: CVV Data Deletion Policy
//
// Tests that CVV never survives past the in-memory authorisation step.
// Each test probes a distinct point in the lifecycle:
//
//  [1] POST /payment/check  — CVV accepted for validation, not echoed back
//  [2] POST /invoices       — CVV stripped at controller boundary
//  [3] DB write             — cvv column is NULL
//  [4] GET /invoices/{id}   — CVV absent from API response
//  [5] GET /invoices        — CVV absent from list response
//  [6] PAN masking          — raw PAN not in DB, only masked token
//  [7] Ciphertext           — pan_ciphertext present and opaque
//  [8] Round-trip           — vault can recover original PAN

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Services\InvoiceNumberGenerator;
use App\Services\Vault\CreditCardVaultService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\TestCase;

uses(DatabaseMigrations::class);

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

function buildCreditCardInvoice(TestCase $test, User $user): array
{
    $invoiceMock = Mockery::mock(InvoiceNumberGenerator::class);
    $invoiceMock->shouldReceive('generate')->once()->andReturn('INV-2026-00000001');
    app()->instance(InvoiceNumberGenerator::class, $invoiceMock);

    $cart    = Cart::factory()->create();
    $product = $test->addProduct();
    CartItem::factory()->create([
        'cart_id'    => $cart->id,
        'product_id' => $product->id,
        'quantity'   => 1,
    ]);

    $expected = app(\App\Services\Postcode\PostcodeService::class)->lookup('NL', '1011AB');

    $payload = [
        'cart_id'              => $cart->id,
        'payment_method'       => 'credit-card',
        'payment_details'      => [
            'credit_card_number' => '4111-1111-1111-1234',
            'expiration_date'    => '12/2030',
            'cvv'                => '999',
            'card_holder_name'   => 'Test User',
        ],
        'billing_street'       => $expected->street,
        'billing_city'         => $expected->city,
        'billing_country'      => 'NL',
        'billing_state'        => $expected->state,
        'billing_postal_code'  => '1011AB',
    ];

    $response = $test->postJson('/invoices', $payload, $test->headers($user));
    return [$response, $payload];
}

// ---------------------------------------------------------------------------
// [1] POST /payment/check — CVV is accepted but never echoed back
// ---------------------------------------------------------------------------

test('[CVV policy] payment/check accepts CVV for validation and does not echo it', function () {
    $response = $this->postJson('/payment/check', [
        'payment_method'  => 'credit-card',
        'payment_details' => [
            'credit_card_number' => '4111-1111-1111-1234',
            'expiration_date'    => '12/2030',
            'cvv'                => '999',
            'card_holder_name'   => 'Test User',
        ],
    ]);

    $response->assertStatus(ResponseAlias::HTTP_OK);

    // Response must not reflect the CVV back in any field.
    $body = $response->getContent();
    expect($body)->not->toContain('999');
    expect($body)->not->toContain('cvv');
    expect($body)->not->toContain('4111');
});

// ---------------------------------------------------------------------------
// [2] POST /invoices — controller strips CVV before service layer
// ---------------------------------------------------------------------------

test('[CVV policy] POST /invoices succeeds and CVV is not present in response', function () {
    $user = User::factory()->create(['role' => 'user']);
    [$response] = buildCreditCardInvoice($this, $user);

    $response->assertStatus(ResponseAlias::HTTP_CREATED);

    $body = $response->getContent();
    expect($body)->not->toContain('"cvv"');
    expect($body)->not->toContain('999');      // the CVV value itself
});

// ---------------------------------------------------------------------------
// [3] DB write — cvv column is NULL
// ---------------------------------------------------------------------------

test('[CVV policy] cvv column is NULL in database after credit card checkout', function () {
    $user = User::factory()->create(['role' => 'user']);
    buildCreditCardInvoice($this, $user);

    $row = DB::table('payment_credit_card_details')->first();

    expect($row)->not->toBeNull();
    expect($row->cvv)->toBeNull();

    // Belt-and-suspenders: assert via Eloquent query too.
    $this->assertDatabaseHas('payment_credit_card_details', ['cvv' => null]);
    $this->assertDatabaseMissing('payment_credit_card_details', ['cvv' => '999']);
});

// ---------------------------------------------------------------------------
// [4] GET /invoices/{id} — CVV not in single-invoice response
// ---------------------------------------------------------------------------

test('[CVV policy] GET /invoices/{id} does not expose CVV', function () {
    $user = User::factory()->create(['role' => 'user']);
    [$createResponse] = buildCreditCardInvoice($this, $user);

    $invoiceId = $createResponse->json('id');
    $response  = $this->getJson("/invoices/{$invoiceId}", $this->headers($user));

    $response->assertStatus(ResponseAlias::HTTP_OK);

    $body = $response->getContent();
    expect($body)->not->toContain('"cvv"');
    expect($body)->not->toContain('999');
});

// ---------------------------------------------------------------------------
// [5] GET /invoices — CVV not in list response
// ---------------------------------------------------------------------------

test('[CVV policy] GET /invoices list does not expose CVV for any payment', function () {
    $user = User::factory()->create(['role' => 'user']);
    buildCreditCardInvoice($this, $user);

    $response = $this->getJson('/invoices', $this->headers($user));
    $response->assertStatus(ResponseAlias::HTTP_OK);

    $body = $response->getContent();
    expect($body)->not->toContain('"cvv"');
    expect($body)->not->toContain('999');
});

// ---------------------------------------------------------------------------
// [6] PAN masking — raw PAN never stored, only masked token
// ---------------------------------------------------------------------------

test('[CVV policy] raw PAN is not stored in database', function () {
    $user = User::factory()->create(['role' => 'user']);
    buildCreditCardInvoice($this, $user);

    $this->assertDatabaseMissing('payment_credit_card_details', [
        'credit_card_number' => '4111-1111-1111-1234',
    ]);
});

test('[CVV policy] masked token is stored with last-4 digits preserved', function () {
    $user = User::factory()->create(['role' => 'user']);
    buildCreditCardInvoice($this, $user);

    $this->assertDatabaseHas('payment_credit_card_details', [
        'credit_card_number' => '****-****-****-1234',
    ]);
});

// ---------------------------------------------------------------------------
// [7] Ciphertext — pan_ciphertext is present and opaque
// ---------------------------------------------------------------------------

test('[CVV policy] pan_ciphertext is stored and does not contain readable card digits', function () {
    $user = User::factory()->create(['role' => 'user']);
    buildCreditCardInvoice($this, $user);

    $row = DB::table('payment_credit_card_details')->first();

    expect($row->pan_ciphertext)->not->toBeNull();
    expect($row->pan_ciphertext)->not->toContain('4111');
    expect($row->pan_ciphertext)->not->toContain('1234');
    expect($row->pan_ciphertext)->not->toContain('999'); // CVV not in ciphertext either
});

// ---------------------------------------------------------------------------
// [8] Round-trip — vault can recover original PAN (authorised path only)
// ---------------------------------------------------------------------------

test('[CVV policy] vault detokenize recovers exact original PAN', function () {
    $user = User::factory()->create(['role' => 'user']);
    buildCreditCardInvoice($this, $user);

    $row   = DB::table('payment_credit_card_details')->first();
    $vault = app(CreditCardVaultService::class);

    expect($vault->detokenize($row->pan_ciphertext))->toBe('4111-1111-1111-1234');
});
