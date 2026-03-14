<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;

class ImageUploadService
{
    public function uploadGameCover(UploadedFile $file, ?string $oldUrl = null): string
    {
        if ($oldUrl) {
            $this->deleteByUrl($oldUrl);
        }

        $result = Cloudinary::upload($file->getRealPath(), [
            'folder'         => 'game-database/covers',
            'transformation' => [
                'width'   => 600,
                'height'  => 800,
                'crop'    => 'fill',
                'quality' => 'auto',
                'fetch_format' => 'auto',
            ],
        ]);

        return $result->getSecurePath();
    }

    public function deleteByUrl(string $url): void
    {
        // Cloudinary Public ID aus URL extrahieren
        preg_match('/\/v\d+\/(.+)\.\w+$/', $url, $matches);
        if (!empty($matches[1])) {
            Cloudinary::destroy($matches[1]);
        }
    }
}
