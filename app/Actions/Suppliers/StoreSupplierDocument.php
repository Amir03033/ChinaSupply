<?php

namespace App\Actions\Suppliers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores supplier documents on the PRIVATE disk under a random name.
 * The MIME type is detected from file contents, not trusted from the client.
 */
class StoreSupplierDocument
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    /** @var array<string, string> detected mime => extension */
    public const ALLOWED = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function handle(Supplier $supplier, UploadedFile $file, DocumentType $type, ?User $uploader, ?string $validUntil = null): SupplierDocument
    {
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());

        if (! isset(self::ALLOWED[$mime])) {
            throw ValidationException::withMessages(['file' => __('portal.errors.file_type')]);
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => __('portal.errors.file_size')]);
        }

        $hash = hash_file('sha256', $file->getRealPath());

        // Same file uploaded again for this supplier: keep the existing record.
        if ($existing = $supplier->documents()->where('sha256', $hash)->where('type', $type)->first()) {
            return $existing;
        }

        $path = $file->storeAs('suppliers/'.$supplier->uuid.'/documents', Str::random(40).'.'.self::ALLOWED[$mime], 'local');

        return $supplier->documents()->create([
            'type' => $type,
            'disk' => 'local',
            'path' => $path,
            'original_name' => Str::limit(preg_replace('/[^\pL\pN._ -]/u', '_', $file->getClientOriginalName()) ?? 'document', 180, ''),
            'mime' => $mime,
            'size' => $file->getSize(),
            'sha256' => $hash,
            'status' => DocumentStatus::Pending,
            'valid_until' => $validUntil,
            'uploaded_by' => $uploader?->id,
        ]);
    }

    public function delete(SupplierDocument $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }
}
