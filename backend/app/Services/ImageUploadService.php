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

        $filename = 'covers/'.Str::uuid().'.'.$file->getClientOriginalExtension();
        $contents = file_get_contents((string) $file->getRealPath());
        if ($contents === false) {
            throw new \RuntimeException('Could not read uploaded file.');
        }
        Storage::disk('public')->put($filename, $contents);

        return Storage::disk('public')->url($filename);
    }

    public function uploadGameImage(UploadedFile $file): string
    {
        $filename = 'game-images/'.Str::uuid().'.'.$file->getClientOriginalExtension();
        $contents = file_get_contents((string) $file->getRealPath());
        if ($contents === false) {
            throw new \RuntimeException('Could not read uploaded file.');
        }
        Storage::disk('public')->put($filename, $contents);

        return Storage::disk('public')->url($filename);
    }

    public function uploadEventImage(UploadedFile $file, ?string $oldUrl = null): string
    {
        if ($oldUrl) {
            $this->deleteByUrl($oldUrl);
        }

        $filename = 'events/'.Str::uuid().'.'.$file->getClientOriginalExtension();
        $contents = file_get_contents((string) $file->getRealPath());
        if ($contents === false) {
            throw new \RuntimeException('Could not read uploaded file.');
        }
        Storage::disk('public')->put($filename, $contents);

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

    /**
     * Downscale + recompress an uploaded image for mail attachment. Always
     * re-encodes as JPEG so attachment size stays predictable regardless of
     * the original format, and corrects EXIF orientation (common on phone
     * photos) since GD otherwise ignores it and the photo would render
     * sideways/upside-down.
     *
     * @return array{filename: string, mime: string, contents: string} contents is base64-encoded
     */
    public function compressForAttachment(UploadedFile $file, int $maxDimension = 1600, int $quality = 80): array
    {
        $raw = (string) file_get_contents((string) $file->getRealPath());
        $source = @imagecreatefromstring($raw);

        if (! $source instanceof \GdImage) {
            // Not a format GD can decode — attach the original bytes untouched
            // rather than fail the whole submission over one odd file.
            return [
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?? 'application/octet-stream',
                'contents' => base64_encode($raw),
            ];
        }

        $source = $this->fixOrientation($source, $file);

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxDimension / max($width, $height));

        if ($scale < 1) {
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }

        ob_start();
        imagejpeg($source, quality: $quality);
        $contents = (string) ob_get_clean();
        imagedestroy($source);

        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.jpg';

        return [
            'filename' => $name,
            'mime' => 'image/jpeg',
            'contents' => base64_encode($contents),
        ];
    }

    private function fixOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data((string) $file->getRealPath());
        $orientation = $exif !== false ? ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, 270, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated instanceof \GdImage && $rotated !== $image) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
