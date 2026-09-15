<?php

namespace Tests\Feature;

use App\Models\TokenPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalWebhookTest extends TestCase
{
    use RefreshDatabase;

    private array $headers = [
        'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        'PAYPAL-CERT-URL' => 'https://api.sandbox.paypal.com/cert',
        'PAYPAL-TRANSMISSION-ID' => 'transmission-1',
        'PAYPAL-TRANSMISSION-SIG' => 'sig',
        'PAYPAL-TRANSMISSION-TIME' => '2026-09-15T12:00:00Z',
    ];

    private function fakeVerification(string $status = 'SUCCESS'): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'fake-token']),
            '*/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => $status,
            ]),
        ]);
    }

    public function test_credits_tokens_for_completed_capture(): void
    {
        $this->fakeVerification();
        $user = User::factory()->member()->create(['tokens' => 5]);
        $purchase = TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'token_amount' => 20,
            'status' => 'CREATED',
        ]);

        $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE-1',
                'status' => 'COMPLETED',
                'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-ORDER-1']],
            ],
        ], $this->headers)->assertOk();

        $this->assertDatabaseHas('token_purchases', [
            'id' => $purchase->id,
            'status' => 'COMPLETED',
            'paypal_capture_id' => 'CAPTURE-1',
        ]);
        $this->assertEquals(25, $user->fresh()->tokens);
    }

    public function test_is_idempotent_with_prior_client_side_capture(): void
    {
        $this->fakeVerification();
        $user = User::factory()->member()->create(['tokens' => 25]);
        TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'token_amount' => 20,
            'status' => 'COMPLETED',
            'paypal_capture_id' => 'CAPTURE-1',
        ]);

        $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE-1',
                'status' => 'COMPLETED',
                'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-ORDER-1']],
            ],
        ], $this->headers)->assertOk();

        $this->assertDatabaseCount('token_transactions', 0);
        $this->assertEquals(25, $user->fresh()->tokens);
    }

    public function test_rejects_invalid_signature(): void
    {
        $this->fakeVerification('FAILURE');
        $user = User::factory()->member()->create(['tokens' => 5]);
        TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'token_amount' => 20,
            'status' => 'CREATED',
        ]);

        $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE-1',
                'status' => 'COMPLETED',
                'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-ORDER-1']],
            ],
        ], $this->headers)->assertStatus(400);

        $this->assertEquals(5, $user->fresh()->tokens);
    }

    public function test_ignores_completed_capture_for_unknown_order(): void
    {
        $this->fakeVerification();

        $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE-X',
                'status' => 'COMPLETED',
                'supplementary_data' => ['related_ids' => ['order_id' => 'DOES-NOT-EXIST']],
            ],
        ], $this->headers)->assertOk();

        $this->assertDatabaseCount('token_purchases', 0);
    }

    public function test_marks_purchase_refunded(): void
    {
        $this->fakeVerification();
        $user = User::factory()->member()->create(['tokens' => 25]);
        TokenPurchase::factory()->create([
            'user_id' => $user->id,
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'token_amount' => 20,
            'status' => 'COMPLETED',
            'paypal_capture_id' => 'CAPTURE-1',
        ]);

        $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'REFUND-1',
                'links' => [
                    ['rel' => 'up', 'href' => 'https://api-m.sandbox.paypal.com/v2/payments/captures/CAPTURE-1'],
                ],
            ],
        ], $this->headers)->assertOk();

        $this->assertDatabaseHas('token_purchases', [
            'paypal_capture_id' => 'CAPTURE-1',
            'status' => 'REFUNDED',
        ]);
    }

    public function test_ignores_unknown_event_types(): void
    {
        $this->fakeVerification();

        $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource' => ['id' => 'whatever'],
        ], $this->headers)->assertOk();
    }
}
