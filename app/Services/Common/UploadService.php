<?php

namespace App\Services\Common;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadService
{
    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'pdf',
        'mp4',
        'webm',
    ];

    public function store(UploadedFile $file, string $folder): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        abort_unless(
            in_array($extension, self::ALLOWED_EXTENSIONS, true),
            422,
            'Unsupported file type.'
        );

        $mime = (string) $file->getMimeType();
        $allowedMime = str_starts_with($mime, 'image/')
            || str_starts_with($mime, 'video/')
            || $mime === 'application/pdf';

        abort_unless($allowedMime, 422, 'File content does not match an allowed media type.');

        $safeFolder = trim(
            preg_replace('/[^a-zA-Z0-9_\/-]/', '', $folder) ?: '',
            '/'
        );

        abort_if($safeFolder === '', 422, 'Invalid upload folder.');

        $directory = public_path('upload/'.$safeFolder);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            abort(500, 'Unable to create upload directory.');
        }

        $filename = Str::uuid().'.'.$extension;
        $file->move($directory, $filename);

        return 'upload/'.$safeFolder.'/'.$filename;
    }

    public function delete(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'upload/')) {
            return;
        }

        $fullPath = public_path($path);

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
