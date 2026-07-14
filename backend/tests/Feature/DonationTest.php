<?php

namespace Tests\Feature;

use App\Mail\DonationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DonationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'games' => ['Catan', 'Carcassonne'],
            'confirmed_complete' => true,
            'form_loaded_at' => (int) (microtime(true) * 1000) - 5000,
        ];
    }

    public function test_store_requires_auth(): void
    {
        $this->postJson('/api/donations', $this->validPayload())->assertUnauthorized();
    }

    public function test_store_requires_games_and_confirmation(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/donations', [
                'form_loaded_at' => (int) (microtime(true) * 1000) - 5000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['games', 'confirmed_complete']);
    }

    public function test_store_rejects_honeypot_filled(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/donations', [...$this->validPayload(), 'website' => 'http://spam.example'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['website']);
    }

    public function test_store_rejects_too_fast_submission(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->postJson('/api/donations', [
                ...$this->validPayload(),
                'form_loaded_at' => (int) (microtime(true) * 1000),
            ])
            ->assertUnprocessable();
    }

    public function test_store_emails_admins(): void
    {
        Mail::fake();

        $user = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user)
            ->postJson('/api/donations', $this->validPayload())
            ->assertCreated();

        Mail::assertQueued(DonationMail::class, function (DonationMail $mail) use ($admin, $user) {
            return $mail->hasTo($admin->email)
                && $mail->donorName === $user->name
                && $mail->games === ['Catan', 'Carcassonne'];
        });
    }

    public function test_store_downscales_and_recompresses_oversized_images(): void
    {
        Mail::fake();

        User::factory()->admin()->create();
        $user = User::factory()->member()->create();

        $oversized = UploadedFile::fake()->image('game.png', 3000, 2000);

        $this->actingAs($user)
            ->post('/api/donations', [
                ...$this->validPayload(),
                'images' => [$oversized],
            ])
            ->assertCreated();

        Mail::assertQueued(DonationMail::class, function (DonationMail $mail) {
            $image = $mail->images[0];

            $this->assertSame('image/jpeg', $image['mime']);
            $this->assertStringEndsWith('.jpg', $image['filename']);

            $decoded = base64_decode($image['contents']);
            [$width, $height] = getimagesizefromstring($decoded);
            $this->assertLessThanOrEqual(1600, max($width, $height));

            return true;
        });
    }

    /**
     * Deliberately forces the database queue driver instead of faking the
     * mailer/queue: Mail::fake() and the test suite's default "sync" queue
     * both skip job-payload serialization, so neither would catch raw binary
     * image bytes breaking DatabaseQueue's json_encode (see DonationController).
     */
    public function test_store_with_image_survives_real_queue_serialization(): void
    {
        config(['queue.default' => 'database']);

        User::factory()->admin()->create();
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/api/donations', [
                ...$this->validPayload(),
                'images' => [UploadedFile::fake()->image('game.jpg', 200, 200)],
            ])
            ->assertCreated();

        $this->assertDatabaseCount('jobs', 1);
    }
}
