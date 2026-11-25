<?php

namespace Tests\Feature;

use App\Models\DamageReport;
use App\Models\Event;
use App\Models\Loan;
use App\Models\PickupLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Members report damage on their running loans; admins read the reports. */
class DamageReportTest extends TestCase
{
    use RefreshDatabase;

    private function report(User $user, Loan $loan, array $extra = []): TestResponse
    {
        return $this->actingAs($user)->post('/api/damage-reports', [
            'loan_id' => $loan->id,
            'description' => 'Spielplan an der Faltkante eingerissen',
            ...$extra,
        ], ['Accept' => 'application/json']);
    }

    public function test_member_reports_damage_with_a_photo(): void
    {
        Storage::fake('public');
        $member = User::factory()->member()->create();
        $loan = Loan::factory()->create(['user_id' => $member->id]);

        $this->report($member, $loan, ['photo' => UploadedFile::fake()->image('riss.jpg', 2400, 1800)])
            ->assertCreated()
            ->assertJsonPath('description', 'Spielplan an der Faltkante eingerissen');

        $report = DamageReport::firstOrFail();
        $this->assertSame($member->id, $report->user_id);
        Storage::disk('public')->assertExists($report->getRawOriginal('photo_url'));
    }

    public function test_photo_is_optional(): void
    {
        $member = User::factory()->member()->create();
        $loan = Loan::factory()->create(['user_id' => $member->id]);

        $this->report($member, $loan)->assertCreated();
        $this->assertNull(DamageReport::firstOrFail()->getRawOriginal('photo_url'));
    }

    public function test_nobody_reports_on_someone_elses_or_a_closed_loan(): void
    {
        $member = User::factory()->member()->create();

        $foreign = Loan::factory()->create();
        $this->report($member, $foreign)->assertForbidden();

        $returned = Loan::factory()->returned()->create(['user_id' => $member->id]);
        $this->report($member, $returned)->assertUnprocessable();

        $this->assertSame(0, DamageReport::count());
    }

    public function test_no_report_before_the_game_was_handed_out(): void
    {
        $member = User::factory()->member()->create();
        $location = PickupLocation::factory()->create(['is_personal' => true]);
        $event = Event::factory()->pickupAt($location)->create();
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'pickup_location_id' => $location->id,
            'pickup_event_id' => $event->id,
            'handed_out_at' => null,
        ]);

        $this->report($member, $loan)->assertUnprocessable()->assertJsonPath('reason', 'awaiting_pickup');
    }

    public function test_description_is_required_and_the_photo_must_be_an_image(): void
    {
        $member = User::factory()->member()->create();
        $loan = Loan::factory()->create(['user_id' => $member->id]);

        $this->report($member, $loan, ['description' => ''])->assertJsonValidationErrors('description');
        $this->report($member, $loan, ['photo' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertJsonValidationErrors('photo');
    }

    public function test_admin_list_is_paged_newest_first_with_meta(): void
    {
        $admin = User::factory()->admin()->create();
        $loan = Loan::factory()->create();
        foreach (range(1, 27) as $i) {
            $this->travel(1)->minutes();
            DamageReport::create([
                'loan_id' => $loan->id,
                'user_id' => $loan->user_id,
                'description' => "Meldung {$i}",
            ]);
        }

        $this->actingAs($admin)->getJson('/api/admin/damage-reports')
            ->assertOk()
            ->assertJsonCount(25, 'data')
            ->assertJsonPath('data.0.description', 'Meldung 27')
            ->assertJsonPath('meta.total', 27)
            ->assertJsonPath('meta.last_page', 2);

        $this->actingAs($admin)->getJson('/api/admin/damage-reports?page=2')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_admin_list_is_for_admins_only(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->getJson('/api/admin/damage-reports')
            ->assertForbidden();
    }
}
