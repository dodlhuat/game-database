<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\ImageUploadService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EventController extends Controller
{
    public function __construct(private ImageUploadService $imageUpload) {}

    public function index(): AnonymousResourceCollection
    {
        return EventResource::collection(
            Event::orderBy('date')->orderBy('time')->get()
        );
    }

    public function store(EventRequest $request): EventResource
    {
        $data = $request->except('image');
        $data['is_all_day'] = $request->boolean('is_all_day');
        if ($data['is_all_day']) {
            $data['time'] = null;
        }

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->imageUpload->uploadEventImage($request->file('image'));
        }

        return new EventResource(Event::create($data));
    }

    public function update(EventRequest $request, Event $event): EventResource
    {
        $data = $request->except('image');
        $data['is_all_day'] = $request->boolean('is_all_day');
        if ($data['is_all_day']) {
            $data['time'] = null;
        }

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->imageUpload->uploadEventImage(
                $request->file('image'),
                $event->image_url,
            );
        }

        $event->update($data);

        return new EventResource($event);
    }

    public function destroy(Event $event): Response
    {
        if ($event->image_url) {
            $this->imageUpload->deleteByUrl($event->image_url);
        }

        $event->delete();

        return response()->noContent();
    }
}
