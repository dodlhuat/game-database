<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\MembershipRenewalReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** The nightly reminder that a membership runs out within three months. */
class MembershipRenewalReminderTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, ?string $expires, ?string $remindedAt = null): User
    {
        return User::factory()->create([
            'role' => $role,
            'membership_expires_at' => $expires,
            'renewal_reminder_sent_at' => $remindedAt,
        ]);
    }

    public function test_reminds_members_and_supporters_within_three_months_once(): void
    {
        Notification::fake();
        $soon = $this->member('MEMBER', now()->addMonth()->toDateTimeString());
        $supporter = $this->member('SUPPORTER', now()->addDays(80)->toDateTimeString());
        $later = $this->member('MEMBER', now()->addMonths(6)->toDateTimeString());
        $expired = $this->member('MEMBER', now()->subDay()->toDateTimeString());
        $plainUser = $this->member('USER', now()->addMonth()->toDateTimeString());

        $this->artisan('members:send-renewal-reminders')->assertSuccessful();

        Notification::assertSentTo([$soon, $supporter], MembershipRenewalReminderNotification::class);
        Notification::assertNotSentTo([$later, $expired, $plainUser], MembershipRenewalReminderNotification::class);
        $this->assertNotNull($soon->fresh()?->renewal_reminder_sent_at);

        // the next night nobody gets it again
        Notification::fake();
        $this->artisan('members:send-renewal-reminders')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_a_reminder_from_the_previous_year_does_not_count(): void
    {
        Notification::fake();
        $member = $this->member('MEMBER', now()->addMonth()->toDateTimeString(), now()->subYear()->toDateTimeString());

        $this->artisan('members:send-renewal-reminders')->assertSuccessful();

        Notification::assertSentTo($member, MembershipRenewalReminderNotification::class);
    }

    public function test_the_mail_names_the_expiry_date(): void
    {
        $member = $this->member('MEMBER', '2027-01-31 00:00:00');

        $mail = (new MembershipRenewalReminderNotification)->toMail($member);

        $this->assertStringContainsString('31.01.2027', implode(' ', array_map('strval', $mail->introLines)).$mail->subject);
    }
}
