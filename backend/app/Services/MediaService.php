<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Reusable image storage service. Uses the "public" disk locally; swapping
 * FILESYSTEM_DISK (or adding an "s3" disk config) is enough to move to an
 * S3-compatible provider without changing calling code.
 */
class MediaService
{
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    public const MAX_KILOBYTES = 5120;

    public function disk(): string
    {
        return config('filesystems.default', 'public');
    }

    /**
     * Store an uploaded image under uploads/{directory} with a random,
     * collision-free filename. Rejects anything that isn't a genuine
     * jpg/png/webp image (extension + MIME are both checked; SVG and
     * executable content are never accepted).
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Unsupported file extension.');
        }

        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new RuntimeException('Unsupported file type.');
        }

        if (@getimagesize($file->getRealPath()) === false) {
            throw new RuntimeException('File is not a valid image.');
        }

        $directory = trim(preg_replace('/[^a-z0-9\/_-]/i', '', $directory), '/');
        $filename = Str::random(40).'.'.$extension;

        if (config('media.driver') === 'cloudinary') {
            return $this->storeOnCloudinary($file, 'uploads/'.$directory, pathinfo($filename, PATHINFO_FILENAME));
        }

        $path = $file->storeAs('uploads/'.$directory, $filename, $this->disk());

        if ($path === false) {
            throw new RuntimeException('Unable to store uploaded file.');
        }

        return $path;
    }

    /**
     * Replace an existing stored file with a newly uploaded one. The new
     * file is written first; the old file is only removed after the
     * caller's database update succeeds (see delete()), so a failed update
     * never leaves the record pointing at a deleted file.
     */
    public function replace(UploadedFile $file, string $directory): string
    {
        return $this->store($file, $directory);
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (config('media.driver') === 'cloudinary') {
            $this->deleteFromCloudinary($path);
            return;
        }

        if (! Str::startsWith($path, 'uploads/')) {
            return;
        }

        Storage::disk($this->disk())->delete($path);
    }

    public function url(?string $path): ?string
    {
        return ! $path ? null : (Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::disk($this->disk())->url($path));
    }

    private function storeOnCloudinary(UploadedFile $file, string $folder, string $publicId): string
    {
        $config = config('services.cloudinary');
        if (! $config['cloud_name'] || ! $config['api_key'] || ! $config['api_secret']) {
            throw new RuntimeException('Cloudinary media storage is not configured.');
        }

        $timestamp = time();
        $signature = sha1("folder={$folder}&public_id={$publicId}&timestamp={$timestamp}{$config['api_secret']}");
        $response = Http::attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post("https://api.cloudinary.com/v1_1/{$config['cloud_name']}/image/upload", [
                'api_key' => $config['api_key'], 'timestamp' => $timestamp, 'signature' => $signature,
                'folder' => $folder, 'public_id' => $publicId,
            ]);

        if (! $response->successful() || ! $response->json('secure_url')) {
            throw new RuntimeException('Cloudinary upload failed.');
        }

        return $response->json('secure_url');
    }

    private function deleteFromCloudinary(string $url): void
    {
        if (! Str::startsWith($url, 'https://res.cloudinary.com/')) return;
        $config = config('services.cloudinary');
        if (! $config['cloud_name'] || ! $config['api_key'] || ! $config['api_secret']) return;
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (! preg_match('#/image/upload/(?:v\d+/)?(.+)$#', $path, $matches)) return;
        $publicId = preg_replace('/\.[^.\/]+$/', '', $matches[1]);
        $timestamp = time();
        $signature = sha1("public_id={$publicId}&timestamp={$timestamp}{$config['api_secret']}");
        Http::asForm()->post("https://api.cloudinary.com/v1_1/{$config['cloud_name']}/image/destroy", [
            'public_id' => $publicId, 'timestamp' => $timestamp, 'api_key' => $config['api_key'], 'signature' => $signature,
        ]);
    }
}
