<?php

namespace Tests\Feature\Admin;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailLogTest extends TestCase
{
    use RefreshDatabase;

    private function log(string $key, string $sentAt): EmailLog
    {
        return EmailLog::create([
            'recipient_email' => 'member@example.com',
            'template_key' => $key,
            'subject' => 'Betreff',
            'sent_at' => $sentAt,
        ]);
    }

    /** @return list<string> */
    private function keys(string $query): array
    {
        return collect(
            $this->getJson('/api/admin/email-logs?'.$query)->assertOk()->json('data')
        )->pluck('template_key')->all();
    }

    public function test_index_requires_admin(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->getJson('/api/admin/email-logs')
            ->assertForbidden();
    }

    public function test_index_filters_by_template_key(): void
    {
        $this->log('user_approved', '2026-10-01 10:00:00');
        $this->log('loan_due_soon', '2026-10-02 10:00:00');
        $this->actingAs(User::factory()->admin()->create());

        $this->assertSame(['loan_due_soon'], $this->keys('template_key=loan_due_soon'));
    }

    public function test_index_filters_by_date_range_inclusive(): void
    {
        $this->log('early', '2026-10-01 23:30:00');
        $this->log('middle', '2026-10-05 08:00:00');
        $this->log('late', '2026-10-09 00:15:00');
        $this->actingAs(User::factory()->admin()->create());

        $this->assertEqualsCanonicalizing(['middle', 'late'], $this->keys('date_from=2026-10-05'));
        $this->assertEqualsCanonicalizing(['early', 'middle'], $this->keys('date_to=2026-10-05'));
        $this->assertSame(['middle'], $this->keys('date_from=2026-10-05&date_to=2026-10-05'));
    }

    public function test_index_ignores_empty_filters(): void
    {
        $this->log('one', '2026-10-01 10:00:00');
        $this->log('two', '2026-10-02 10:00:00');
        $this->actingAs(User::factory()->admin()->create());

        $this->assertEqualsCanonicalizing(['one', 'two'], $this->keys('template_key=&date_from=&date_to='));
    }
}
