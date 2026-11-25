<?php

namespace Tests\Feature\Admin;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Newsletters go out only to active members who opted in. */
class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_reaches_only_active_members_who_opted_in(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create(['newsletter_opt_in' => false]);
        $yes = User::factory()->create(['status' => 'ACTIVE', 'newsletter_opt_in' => true]);
        $no = User::factory()->create(['status' => 'ACTIVE', 'newsletter_opt_in' => false]);
        $suspended = User::factory()->create(['status' => 'SUSPENDED', 'newsletter_opt_in' => true]);

        $this->actingAs($admin)
            ->postJson('/api/admin/newsletters', ['subject' => 'Spieleabend', 'body' => "Hallo!\nBis Freitag."])
            ->assertCreated()
            ->assertJsonPath('recipient_count', 1)
            ->assertJsonPath('sender.id', $admin->id);

        Mail::assertQueued(NewsletterMail::class, fn (NewsletterMail $m) => $m->hasTo($yes->email));
        Mail::assertNotQueued(NewsletterMail::class, fn (NewsletterMail $m) => $m->hasTo($no->email) || $m->hasTo($suspended->email));
        $this->assertSame(1, Newsletter::count());
    }

    public function test_body_is_escaped_and_line_breaks_are_kept_once(): void
    {
        $html = (new NewsletterMail('Betreff', "Zeile 1\nZeile 2 <script>x</script>"))->render();

        $this->assertStringContainsString('Zeile 1<br />', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('pre-line', $html);
    }

    public function test_subject_and_body_are_required(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/admin/newsletters', [])
            ->assertJsonValidationErrors(['subject', 'body']);
    }

    public function test_history_lists_the_latest_first(): void
    {
        $admin = User::factory()->admin()->create();
        Newsletter::create(['subject' => 'Alt', 'body' => 'x', 'sent_at' => now()->subWeek(), 'recipient_count' => 3, 'sent_by' => $admin->id]);
        Newsletter::create(['subject' => 'Neu', 'body' => 'y', 'sent_at' => now(), 'recipient_count' => 4, 'sent_by' => $admin->id]);

        $this->actingAs($admin)->getJson('/api/admin/newsletters')
            ->assertOk()
            ->assertJsonPath('data.0.subject', 'Neu')
            ->assertJsonPath('data.0.sender.id', $admin->id);
    }

    public function test_members_cannot_send_newsletters(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->member()->create())
            ->postJson('/api/admin/newsletters', ['subject' => 'x', 'body' => 'y'])
            ->assertForbidden();
        Mail::assertNothingQueued();
    }
}
