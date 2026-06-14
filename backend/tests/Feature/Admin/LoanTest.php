<?php

namespace Tests\Feature\Admin;

use App\Models\Copy;
use App\Models\EmailTemplate;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanOverdueReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_index_returns_all_loans(): void
    {
        Loan::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->getJson('/api/admin/loans')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_index_requires_admin(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->getJson('/api/admin/loans')
            ->assertForbidden();
    }

    public function test_mark_overdue_updates_status(): void
    {
        $loan = Loan::factory()->create(['status' => 'ACTIVE']);

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/loans/{$loan->id}/overdue")
            ->assertOk()
            ->assertJsonPath('data.status', 'OVERDUE');
    }

    public function test_mark_overdue_fails_for_returned_loan(): void
    {
        $loan = Loan::factory()->returned()->create();

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/loans/{$loan->id}/overdue")
            ->assertStatus(422);
    }

    public function test_return_marks_loan_returned(): void
    {
        $copy = Copy::factory()->create();
        $loan = Loan::factory()->create(['copy_id' => $copy->id, 'status' => 'ACTIVE']);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/loans/{$loan->id}/return", [
                'return_condition' => 'GOOD',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'RETURNED');

        $this->assertDatabaseHas('copies', ['id' => $copy->id, 'condition' => 'REVIEW']);
    }

    public function test_return_fails_for_already_returned_loan(): void
    {
        $loan = Loan::factory()->returned()->create();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/loans/{$loan->id}/return", [
                'return_condition' => 'GOOD',
            ])
            ->assertStatus(422);
    }

    public function test_send_overdue_reminder_queues_notification(): void
    {
        Notification::fake();
        EmailTemplate::create([
            'key' => 'loan_overdue_reminder',
            'subject' => 'Überfällig: {game}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Bitte zurückbringen.</p>',
            'action_text' => 'Zum Dashboard',
        ]);

        $loan = Loan::factory()->overdue()->create();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/loans/{$loan->id}/remind")
            ->assertOk()
            ->assertJsonPath('data.status', 'OVERDUE');

        Notification::assertSentTo($loan->user, LoanOverdueReminder::class);
    }

    public function test_send_overdue_reminder_records_timestamp(): void
    {
        Notification::fake();
        EmailTemplate::create([
            'key' => 'loan_overdue_reminder',
            'subject' => 'Test',
            'greeting' => 'Hi,',
            'body' => '<p>Test</p>',
            'action_text' => null,
        ]);

        $loan = Loan::factory()->overdue()->create();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/loans/{$loan->id}/remind")
            ->assertOk();

        $this->assertNotNull($loan->fresh()->overdue_reminder_sent_at);
    }

    public function test_send_overdue_reminder_fails_for_non_overdue_loan(): void
    {
        $loan = Loan::factory()->create(['status' => 'ACTIVE']);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/loans/{$loan->id}/remind")
            ->assertStatus(422);
    }

    public function test_send_overdue_reminder_requires_admin(): void
    {
        $loan = Loan::factory()->overdue()->create();

        $this->actingAs(User::factory()->member()->create())
            ->postJson("/api/admin/loans/{$loan->id}/remind")
            ->assertForbidden();
    }
}
