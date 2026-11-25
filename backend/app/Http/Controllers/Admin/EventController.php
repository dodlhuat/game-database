<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\PackageLoan;
use App\Models\User;
use App\Notifications\PickupEventMoved;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

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
        // Only checked fields — the raw request could set image_url directly
        $data = collect($request->validated())->except(['image', 'remove_image'])->all();
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
        // Only checked fields — the raw request could set image_url directly
        $data = collect($request->validated())->except(['image', 'remove_image'])->all();
        $data['is_all_day'] = $request->boolean('is_all_day');
        if ($data['is_all_day']) {
            $data['time'] = null;
            $data['end_time'] = null;
        }

        if ($blocked = $this->bookedChangeBlocker($event, $data)) {
            return $blocked;
        }
        $previously = PickupEventMoved::when($event);

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->imageUpload->uploadEventImage(
                $request->file('image'),
                $event->image_url,
            );
        } elseif ($request->boolean('remove_image') && $event->image_url) {
            $this->imageUpload->delete($event->image_url);
            $data['image_url'] = null;
        }

        $event->update($data);

        if ($event->isPickup() && $event->wasChanged(['date', 'time', 'end_time'])) {
            $this->moveBookings($event->refresh(), $previously);
        }

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

        if ($locationId !== $event->pickup_location_id) {
            return response()->json([
                'message' => "Für diesen Abholtermin gibt es {$bookings} Buchung(en). Der Ort kann daher nicht mehr geändert werden.",
                'reason' => 'has_bookings',
            ], 422);
        }

        $moved = ($data['date'] ?? null) !== $event->date->format('Y-m-d')
            || $time($data['time'] ?? null) !== $time($event->time)
            || $time($data['end_time'] ?? null) !== $time($event->end_time);
        $handedOut = Loan::where('pickup_event_id', $event->id)->whereNotNull('handed_out_at')->exists();

        if ($moved && $handedOut) {
            return response()->json([
                'message' => 'Bei diesem Abholtermin wurden schon Spiele ausgegeben. Datum und Uhrzeit können daher nicht mehr geändert werden.',
                'reason' => 'handed_out',
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

    /**
     * The event moved: bookings follow with pickup and due date, the day-before
     * reminder is sent again for the new date, and every member hears about it.
     */
    private function moveBookings(Event $event, string $previously): void
    {
        $start = $event->date->copy()->startOfDay();
        $due = $start->copy()->addWeeks(LoanSetting::instance()->loan_duration_weeks);
        $dates = ['start_date' => $start->toDateString(), 'due_date' => $due->toDateString()];

        $members = DB::transaction(function () use ($event, $dates) {
            $loans = Loan::where('pickup_event_id', $event->id)->whereIn('status', Loan::OPEN_STATUSES);
            $userIds = (clone $loans)->pluck('user_id')->unique();
            $loans->update([...$dates, 'pickup_reminder_sent_at' => null]);
            PackageLoan::where('pickup_event_id', $event->id)->where('status', 'ACTIVE')->update($dates);

            return User::whereKey($userIds)->get();
        });

        foreach ($members as $member) {
            try {
                $member->notify(new PickupEventMoved($event, $previously));
            } catch (Throwable $e) {
                report($e);
            }
        }
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

        // Cancelled no-shows only keep the reference for history; let the event go
        Loan::where('pickup_event_id', $event->id)->where('status', 'CANCELLED')->update(['pickup_event_id' => null]);
        PackageLoan::where('pickup_event_id', $event->id)->where('status', 'CANCELLED')->update(['pickup_event_id' => null]);

        $this->imageUpload->delete($event->image_url);

        $event->delete();

        return response()->noContent();
    }
}
