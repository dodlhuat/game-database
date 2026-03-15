<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    public function uploadGameCover(UploadedFile $file, ?string $oldUrl = null): string
    {
        if ($oldUrl) {
            $this->deleteByUrl($oldUrl);
        }

        $filename = 'covers/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        Storage::disk('public')->put($filename, file_get_contents($file->getRealPath()));

        return Storage::disk('public')->url($filename);
    }

    public function deleteByUrl(string $url): void
    {
        // Pfad relativ zum public-Disk extrahieren
        $path = preg_replace('#^.*/storage/#', '', $url);
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
