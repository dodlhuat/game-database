<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EventController extends Controller
{
    public function __construct(private ImageUploadService $imageUpload) {}

    public function index(): AnonymousResourceCollection
    {
        return EventResource::collection(
            Event::with('pickupLocation')->orderBy('date')->orderBy('time')->get()
        );
    }

    public function store(EventRequest $request): EventResource
    {
        $data = $request->except('image');
        $data['is_all_day'] = $request->boolean('is_all_day');
        if ($data['is_all_day']) {
            $data['time'] = null;
            $data['end_time'] = null;
        }

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->imageUpload->uploadEventImage($request->file('image'));
        }

        return new EventResource(Event::create($data)->load('pickupLocation'));
    }

    public function update(EventRequest $request, Event $event): EventResource|JsonResponse
    {
        $data = $request->except('image');
        $data['is_all_day'] = $request->boolean('is_all_day');
        if ($data['is_all_day']) {
            $data['time'] = null;
            $data['end_time'] = null;
        }

        if ($blocked = $this->bookedChangeBlocker($event, $data)) {
            return $blocked;
        }

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->imageUpload->uploadEventImage(
                $request->file('image'),
                $event->image_url,
            );
        }

        $event->update($data);

        return new EventResource($event->load('pickupLocation'));
    }

    /**
     * Members already booked this pickup event for its place and time, so
     * those stay fixed while bookings exist, and the cap cannot drop below
     * them.
     *
     * @param  array<string, mixed>  $data
     */
    private function bookedChangeBlocker(Event $event, array $data): ?JsonResponse
    {
        if (! $event->isPickup()) {
            return null;
        }
        $bookings = $event->bookingsCount();
        if ($bookings === 0) {
            return null;
        }

        $time = fn (mixed $v): ?string => is_string($v) && $v !== '' ? substr($v, 0, 5) : null;
        $locationId = is_numeric($data['pickup_location_id'] ?? null) ? (int) $data['pickup_location_id'] : null;
        $moved = $locationId !== $event->pickup_location_id
            || ($data['date'] ?? null) !== $event->date->format('Y-m-d')
            || $time($data['time'] ?? null) !== $time($event->time)
            || $time($data['end_time'] ?? null) !== $time($event->end_time);

        if ($moved) {
            return response()->json([
                'message' => "Für diesen Abholtermin gibt es {$bookings} Buchung(en). Ort, Datum und Uhrzeit können daher nicht mehr geändert werden.",
                'reason' => 'has_bookings',
            ], 422);
        }

        $max = $data['max_loans'] ?? null;
        if (is_numeric($max) && (int) $max < $bookings) {
            return response()->json([
                'message' => "Es gibt bereits {$bookings} Buchungen — die Obergrenze kann nicht darunter liegen.",
                'errors' => ['max_loans' => ["Mindestens {$bookings}."]],
            ], 422);
        }

        return null;
    }

    public function destroy(Event $event): Response|JsonResponse
    {
        $bookings = $event->bookingsCount();
        if ($bookings > 0) {
            return response()->json([
                'message' => "Für diesen Abholtermin gibt es {$bookings} Buchung(en). Er kann erst gelöscht werden, wenn sie storniert sind.",
                'reason' => 'has_bookings',
            ], 422);
        }

        $this->imageUpload->delete($event->image_url);

        $event->delete();

        return response()->noContent();
    }
}
