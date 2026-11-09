<?php

namespace App\Actions\Sourcing;

use App\Models\SourcingRequest;
use App\Models\SourcingRequestFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Buyer reference files (photos, drawings, specs) on the private disk.
 * Type is detected from the file contents.
 */
class StoreRequestFile
{
    public const MAX_FILES = 8;

    public const MAX_BYTES = 10 * 1024 * 1024;

    /** @var array<string, string> */
    public const ALLOWED = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function handle(SourcingRequest $request, UploadedFile $file, ?User $by): SourcingRequestFile
    {
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());

        if (! isset(self::ALLOWED[$mime])) {
            throw ValidationException::withMessages(['files' => __('requests.errors.file_type')]);
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['files' => __('requests.errors.file_size')]);
        }

        $path = $file->storeAs('requests/'.$request->uuid, Str::random(40).'.'.self::ALLOWED[$mime], 'local');

        return $request->files()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => Str::limit(preg_replace('/[^\pL\pN._ -]/u', '_', $file->getClientOriginalName()) ?? 'file', 180, ''),
            'mime' => $mime,
            'size' => $file->getSize(),
            'uploaded_by' => $by?->id,
        ]);
    }
}
