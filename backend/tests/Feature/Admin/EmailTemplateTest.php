<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\EmailTemplateController;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    /** Every template a notification sends must be editable in the admin. */
    public function test_every_template_used_by_a_notification_is_editable(): void
    {
        $used = [];
        foreach ((new Finder)->files()->in(app_path('Notifications'))->name('*.php') as $file) {
            preg_match_all("/buildFromTemplate\(\s*'([a-z0-9_]+)'/", $file->getContents(), $m);
            array_push($used, ...$m[1]);
        }

        $this->assertNotEmpty($used);
        $this->assertSame(
            [],
            array_values(array_diff(array_unique($used), EmailTemplateController::keys())),
            'Notification templates missing from EmailTemplateController::$defaults',
        );
    }

    public function test_admin_list_includes_the_cancellation_templates(): void
    {
        $keys = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/admin/email-templates')
            ->assertOk()
            ->json('data.*.key');

        $this->assertContains('membership_cancellation_user', $keys);
        $this->assertContains('membership_cancellation_admin', $keys);
    }

    public function test_reset_restores_the_default_text(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->getJson('/api/admin/email-templates');

        EmailTemplate::where('key', 'membership_cancellation_user')->update(['subject' => 'Geändert']);

        $this->actingAs($admin)
            ->postJson('/api/admin/email-templates/membership_cancellation_user/reset')
            ->assertOk()
            ->assertJsonPath('data.subject', 'Bestätigung deiner Kündigung');
    }

    public function test_admin_saves_an_edited_template(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson('/api/admin/email-templates/user_approved', [
            'subject' => 'Willkommen an Bord',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Dein Konto ist frei.</p>',
            'action_text' => null,
        ])->assertOk()->assertJsonPath('data.subject', 'Willkommen an Bord');

        $template = EmailTemplate::where('key', 'user_approved')->firstOrFail();
        $this->assertSame('<p>Dein Konto ist frei.</p>', $template->body);
        $this->assertNull($template->action_text);
    }

    public function test_a_template_needs_subject_greeting_and_body(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->putJson('/api/admin/email-templates/user_approved', ['subject' => '', 'greeting' => '', 'body' => ''])
            ->assertJsonValidationErrors(['subject', 'greeting', 'body']);
    }

    public function test_members_cannot_edit_templates(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->putJson('/api/admin/email-templates/user_approved', ['subject' => 'x', 'greeting' => 'x', 'body' => 'x'])
            ->assertForbidden();
    }
}
