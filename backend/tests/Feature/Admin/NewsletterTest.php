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
        $html = (new NewsletterMail('Betreff', "Zeile 1\nZeile 2 <script>x</script>", User::factory()->create()))->render();

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

    // ── Unsubscribing ────────────────────────────────────────────

    private function mailFor(User $user): NewsletterMail
    {
        return new NewsletterMail('Spieleabend', 'Hallo!', $user);
    }

    public function test_every_mail_carries_a_footer_link_and_one_click_headers(): void
    {
        $user = User::factory()->create(['newsletter_opt_in' => true]);
        $mail = $this->mailFor($user);

        $this->assertStringContainsString(e($mail->unsubscribePageUrl()), $mail->render());
        $this->assertStringStartsWith(config()->string('frontend.url').'/newsletter/abmelden?user='.$user->id.'&signature=', $mail->unsubscribePageUrl());

        $headers = $mail->headers()->text;
        $this->assertSame('<'.$mail->signedUnsubscribeUrl().'>', $headers['List-Unsubscribe']);
        $this->assertSame('List-Unsubscribe=One-Click', $headers['List-Unsubscribe-Post']);
    }

    public function test_one_click_post_from_the_mail_client_unsubscribes(): void
    {
        $user = User::factory()->create(['newsletter_opt_in' => true]);

        $this->post($this->mailFor($user)->signedUnsubscribeUrl(), ['List-Unsubscribe' => 'One-Click'])->assertOk();
        $this->assertFalse($user->fresh()?->newsletter_opt_in);
    }

    public function test_the_footer_page_posts_the_same_signature_without_logging_in(): void
    {
        $user = User::factory()->create(['newsletter_opt_in' => true]);
        parse_str((string) parse_url($this->mailFor($user)->unsubscribePageUrl(), PHP_URL_QUERY), $query);

        $this->postJson("/api/newsletter/unsubscribe/{$query['user']}?signature={$query['signature']}")->assertOk();
        $this->assertFalse($user->fresh()?->newsletter_opt_in);

        // a second click does no harm
        $this->postJson("/api/newsletter/unsubscribe/{$query['user']}?signature={$query['signature']}")->assertOk();
    }

    public function test_a_forged_or_borrowed_signature_unsubscribes_nobody(): void
    {
        $mia = User::factory()->create(['newsletter_opt_in' => true]);
        $tom = User::factory()->create(['newsletter_opt_in' => true]);
        parse_str((string) parse_url($this->mailFor($mia)->signedUnsubscribeUrl(), PHP_URL_QUERY), $query);

        $this->postJson("/api/newsletter/unsubscribe/{$tom->id}?signature={$query['signature']}")
            ->assertForbidden()
            ->assertJsonPath('reason', 'invalid_link');
        $this->postJson("/api/newsletter/unsubscribe/{$mia->id}?signature=falsch")->assertForbidden();
        $this->postJson("/api/newsletter/unsubscribe/{$mia->id}")->assertForbidden();

        $this->assertTrue($mia->fresh()?->newsletter_opt_in);
        $this->assertTrue($tom->fresh()?->newsletter_opt_in);
    }

    public function test_sent_mails_link_to_their_own_recipient(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create(['newsletter_opt_in' => false]);
        $mia = User::factory()->create(['status' => 'ACTIVE', 'newsletter_opt_in' => true]);

        $this->actingAs($admin)->postJson('/api/admin/newsletters', ['subject' => 'x', 'body' => 'y'])->assertCreated();

        Mail::assertQueued(NewsletterMail::class, fn (NewsletterMail $m) => $m->hasTo($mia->email) && $m->recipient->is($mia));
    }
}
