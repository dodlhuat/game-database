<?php

namespace Tests\Feature\Admin;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every mail template lives in three places: the seeding migration, the
 * admin's reset defaults and the admin page's list. A key missing in one of
 * them means an empty mail, a reset that silently does nothing, or a
 * template nobody can edit — so check all mails the app sends against all three.
 */
class EmailTemplateConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    private function keysTheAppSends(): array
    {
        $keys = [];
        foreach (glob(app_path('Notifications/*.php')) ?: [] as $file) {
            preg_match_all("/buildFromTemplate\\(\\s*'([a-z_]+)'/", (string) file_get_contents($file), $m);
            array_push($keys, ...$m[1]);
        }
        $keys = array_values(array_unique($keys));
        sort($keys);

        return $keys;
    }

    public function test_the_scan_finds_the_mails(): void
    {
        $this->assertGreaterThan(20, count($this->keysTheAppSends()));
    }

    public function test_every_mail_has_a_seeded_template_with_content(): void
    {
        $missing = array_values(array_filter(
            $this->keysTheAppSends(),
            fn (string $key) => blank(EmailTemplate::where('key', $key)->value('subject')),
        ));

        $this->assertSame([], $missing, 'templates without a migration seeding them');
    }

    public function test_every_mail_can_be_reset_to_a_default(): void
    {
        $admin = User::factory()->admin()->create();
        $broken = [];

        foreach ($this->keysTheAppSends() as $key) {
            EmailTemplate::where('key', $key)->update(['subject' => 'geändert']);
            $this->actingAs($admin)->postJson("/api/admin/email-templates/{$key}/reset");
            if (EmailTemplate::where('key', $key)->value('subject') === 'geändert') {
                $broken[] = $key;
            }
        }

        $this->assertSame([], $broken, 'templates without a reset default in EmailTemplateController');
    }

    public function test_every_mail_is_listed_on_the_admin_page(): void
    {
        $file = base_path('../frontend/utils/emailTemplates.ts');
        if (! is_file($file)) {
            // the backend container only mounts backend/
            $this->markTestSkipped('frontend/ is not available here');
        }
        $page = (string) file_get_contents($file);
        $missing = array_values(array_filter(
            $this->keysTheAppSends(),
            fn (string $key) => ! preg_match("/^\\s+{$key}: \\{/m", $page),
        ));

        $this->assertSame([], $missing, 'templates missing in frontend/utils/emailTemplates.ts');
    }
}
