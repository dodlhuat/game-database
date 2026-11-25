<?php

namespace Tests\Feature\Admin;

use App\Models\Loan;
use App\Models\PackageLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The admin list of package loans and when one counts as overdue. */
class PackageLoanAdminTest extends TestCase
{
    use RefreshDatabase;

    private function packageLoan(string $status, string $due): PackageLoan
    {
        return PackageLoan::factory()->create(['status' => $status, 'due_date' => $due]);
    }

    public function test_overdue_tab_finds_running_packages_past_their_due_date(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 10)->setTime(10, 0));
        $late = $this->packageLoan('ACTIVE', '2026-12-09');
        $this->packageLoan('ACTIVE', '2026-12-10');    // due today: not late yet
        $this->packageLoan('RETURNED', '2026-12-01');  // back, however late

        $ids = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/package-loans?status=OVERDUE')
            ->assertOk()
            ->json('data.*.id');

        $this->assertSame([$late->id], $ids);
    }

    public function test_other_tabs_filter_by_status(): void
    {
        $running = $this->packageLoan('ACTIVE', now()->addWeek()->toDateString());
        $this->packageLoan('RETURNED', now()->subWeek()->toDateString());

        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/package-loans?status=ACTIVE')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $running->id);
    }

    public function test_overdue_flag_starts_the_day_after_the_due_date(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 10)->setTime(10, 0));
        $admin = User::factory()->admin()->create();
        $dueToday = $this->packageLoan('ACTIVE', '2026-12-10');
        $dueYesterday = $this->packageLoan('ACTIVE', '2026-12-09');

        $rows = collect($this->actingAs($admin)->getJson('/api/admin/package-loans')->json('data'))->keyBy('id');
        $this->assertFalse($rows[$dueToday->id]['is_overdue']);
        $this->assertTrue($rows[$dueYesterday->id]['is_overdue']);

        // single loans follow the same rule
        $loan = Loan::factory()->create(['status' => 'ACTIVE', 'due_date' => '2026-12-10']);
        $this->actingAs($loan->user)->getJson('/api/loans')
            ->assertOk()
            ->assertJsonPath('data.0.is_overdue', false);
    }
}
