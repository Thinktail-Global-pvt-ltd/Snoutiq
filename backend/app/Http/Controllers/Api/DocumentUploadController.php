<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentUpload;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentUploadController extends Controller
{
    /**
     * Persist uploaded document metadata and store files.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required'],
            'file_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'pet_id' => ['nullable', 'integer', 'exists:pets,id'],
            'record_type' => ['required', 'string', 'max:100'],
            'record_label' => ['required', 'string', 'max:150'],
            'source' => ['required', 'string', 'max:60'],
            'uploaded_at' => ['nullable', 'date'],
        ]);

        $files = $this->normalizeFiles($request->file('file'));
        if (empty($files)) {
            throw ValidationException::withMessages([
                'file' => ['At least one valid file is required.'],
            ]);
        }

        $storedFiles = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            if (! $file->isValid()) {
                $this->cleanupStoredFiles($storedFiles);
                throw ValidationException::withMessages([
                    'file' => [$file->getErrorMessage() ?: 'File upload failed.'],
                ]);
            }

            try {
                $path = $file->store('document-uploads', 'public');
            } catch (\Throwable $e) {
                $this->cleanupStoredFiles($storedFiles);
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to store uploaded file.',
                ], 500);
            }

            if (! $path) {
                $this->cleanupStoredFiles($storedFiles);
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to store uploaded file.',
                ], 500);
            }

            $storedFiles[] = [
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'url' => Storage::disk('public')->url($path),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        $upload = DocumentUpload::create([
            'user_id' => $validated['user_id'],
            'pet_id' => $validated['pet_id'] ?? null,
            'record_type' => $validated['record_type'],
            'record_label' => $validated['record_label'],
            'source' => $validated['source'],
            'file_count' => $validated['file_count'] ?? count($storedFiles),
            'files_json' => $storedFiles,
            'uploaded_at' => isset($validated['uploaded_at'])
                ? Carbon::parse($validated['uploaded_at'])
                : now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $upload->id,
                'user_id' => $upload->user_id,
                'pet_id' => $upload->pet_id,
                'record_type' => $upload->record_type,
                'record_label' => $upload->record_label,
                'source' => $upload->source,
                'file_count' => $upload->file_count,
                'uploaded_at' => optional($upload->uploaded_at)->toIso8601String(),
                'files' => $storedFiles,
            ],
        ], 201);
    }

    /**
     * Normalize the incoming file payload so both single and multi-part inputs work.
     */
    private function normalizeFiles($input): array
    {
        if ($input instanceof UploadedFile) {
            return [$input];
        }

        if (is_array($input)) {
            return array_values(array_filter($input));
        }

        return [];
    }

    /**
     * Delete already-saved files if a later step fails.
     */
    private function cleanupStoredFiles(array $storedFiles): void
    {
        $disk = Storage::disk('public');
        foreach ($storedFiles as $file) {
            if (! empty($file['stored_path'])) {
                $disk->delete($file['stored_path']);
            }
        }
    }
}
