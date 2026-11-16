<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SponsorRequest;
use App\Http\Resources\SponsorResource;
use App\Models\Sponsor;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SponsorController extends Controller
{
    public function __construct(private ImageUploadService $imageUpload) {}

    public function index(): AnonymousResourceCollection
    {
        return SponsorResource::collection(Sponsor::ordered()->get());
    }

    public function store(SponsorRequest $request): SponsorResource
    {
        $data = $request->safe()->only(['name', 'url']);
        $data['logo_on_light'] = $request->boolean('logo_on_light');
        $data['is_active'] = $request->boolean('is_active', true);
        $maxOrder = Sponsor::withTrashed()->max('sort_order');
        $data['sort_order'] = (is_numeric($maxOrder) ? (int) $maxOrder : 0) + 1;

        if ($request->hasFile('logo')) {
            $data['logo_url'] = $this->imageUpload->uploadSponsorLogo($request->file('logo'));
        }

        return new SponsorResource(Sponsor::create($data));
    }

    public function update(SponsorRequest $request, Sponsor $sponsor): SponsorResource
    {
        $data = $request->safe()->only(['name', 'url']);
        $data['url'] ??= null;
        $data['logo_on_light'] = $request->boolean('logo_on_light');
        $data['is_active'] = $request->boolean('is_active', $sponsor->is_active);

        if ($request->hasFile('logo')) {
            $data['logo_url'] = $this->imageUpload->uploadSponsorLogo(
                $request->file('logo'),
                $sponsor->logo_url,
            );
        } elseif ($request->boolean('remove_logo')) {
            $this->imageUpload->delete($sponsor->logo_url);
            $data['logo_url'] = null;
        }

        $sponsor->update($data);

        return new SponsorResource($sponsor);
    }

    public function toggle(Sponsor $sponsor): SponsorResource
    {
        $sponsor->update(['is_active' => ! $sponsor->is_active]);

        return new SponsorResource($sponsor);
    }

    public function reorder(Request $request): Response
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:sponsors,id'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (array_values($validated['ids']) as $position => $id) {
                Sponsor::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return response()->noContent();
    }

    /** Soft delete — the logo file is kept so the record stays restorable. */
    public function destroy(Sponsor $sponsor): Response
    {
        $sponsor->delete();

        return response()->noContent();
    }
}
