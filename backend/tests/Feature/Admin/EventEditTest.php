<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Editing an event's picture and text from the admin form. */
class EventEditTest extends TestCase
{
    use RefreshDatabase;

    /** @param  array<string, mixed>  $fields */
    private function save(?Event $event, array $fields): TestResponse
    {
        $body = ['title' => 'Spieleabend', 'date' => '2026-12-12', 'is_all_day' => '1', ...$fields];
        $admin = User::factory()->admin()->create();
        $url = $event ? "/api/admin/events/{$event->id}?_method=PUT" : '/api/admin/events';

        return $this->actingAs($admin)->post($url, $body, ['Accept' => 'application/json']);
    }

    public function test_upload_replace_and_remove_the_image(): void
    {
        Storage::fake('public');

        $id = $this->save(null, ['image' => UploadedFile::fake()->image('a.jpg', 800, 600)])->assertCreated()->json('data.id');
        $event = Event::findOrFail($id);
        $first = (string) $event->getRawOriginal('image_url');
        Storage::disk('public')->assertExists($first);

        $this->save($event, ['image' => UploadedFile::fake()->image('b.jpg', 800, 600)])->assertOk();
        $second = (string) $event->fresh()?->getRawOriginal('image_url');
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->save($event, ['remove_image' => '1'])->assertOk()->assertJsonPath('data.image_url', null);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_the_description_can_be_cleared(): void
    {
        $event = Event::factory()->create(['description' => '<p>Alt</p>']);

        $this->save($event, ['description' => ''])->assertOk();

        $this->assertNull($event->fresh()?->description);
    }

    public function test_unchecked_fields_are_ignored(): void
    {
        $event = Event::factory()->create(['image_url' => null]);

        $this->save($event, ['image_url' => 'https://evil.example/x.png'])->assertOk();

        $this->assertNull($event->fresh()?->getRawOriginal('image_url'));
    }
}
