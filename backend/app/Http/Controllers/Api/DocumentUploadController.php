<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentUpload;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DocumentUploadController extends Controller
{
    /**
     * List document uploads for a given user (optionally filtered by pet/record_type).
     */
    public function index(Request $request, int $userId)
    {
        $filters = $request->validate([
            'pet_id' => ['nullable', 'integer'],
            'record_type' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:60'],
        ]);

        $hasBlobColumns = $this->blobColumnsReady();

        $selectColumns = [
            'id',
            'user_id',
            'pet_id',
            'record_type',
            'record_label',
            'source',
            'file_count',
            'files_json',
            'uploaded_at',
        ];
        if ($hasBlobColumns) {
            $selectColumns[] = 'file_mime';
            $selectColumns[] = 'file_name';
            $selectColumns[] = 'file_size';
        }

        $query = DocumentUpload::query()
            ->select($selectColumns)
            ->where('user_id', $userId)
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id');

        if (! empty($filters['pet_id'])) {
            $query->where('pet_id', $filters['pet_id']);
        }

        if (! empty($filters['record_type'])) {
            $query->where('record_type', $filters['record_type']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        $uploads = $query->get()->map(function (DocumentUpload $upload) use ($hasBlobColumns) {
            return [
                'id' => $upload->id,
                'user_id' => $upload->user_id,
                'pet_id' => $upload->pet_id,
                'record_type' => $upload->record_type,
                'record_label' => $upload->record_label,
                'source' => $upload->source,
                'file_count' => $upload->file_count,
                'uploaded_at' => optional($upload->uploaded_at)->toIso8601String(),
                'files' => $upload->files_json ?? [],
                'file_name' => $hasBlobColumns ? ($upload->file_name ?? null) : null,
                'file_mime' => $hasBlobColumns ? ($upload->file_mime ?? null) : null,
                'file_size' => $hasBlobColumns ? ($upload->file_size ?? null) : null,
                'blob_url' => $hasBlobColumns ? route('api.documents.blob', ['uploadId' => $upload->id]) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $uploads,
        ]);
    }

    /**
     * Persist uploaded document metadata and store files.
     */
    public function store(Request $request)
    {
        if (! $this->blobColumnsReady()) {
            return response()->json([
                'success' => false,
                'message' => 'document_uploads blob columns are missing. Please run migrations.',
            ], 500);
        }

        $validated = $request->validate([
            'file' => ['required'],
            'file_count' => ['nullable', 'integer', 'min:1', 'max:1'],
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
        if (count($files) !== 1) {
            throw ValidationException::withMessages([
                'file' => ['Only one file is supported for this endpoint.'],
            ]);
        }

        /** @var UploadedFile $file */
        $file = $files[0];
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            throw ValidationException::withMessages([
                'file' => [$file instanceof UploadedFile ? ($file->getErrorMessage() ?: 'File upload failed.') : 'Invalid file payload.'],
            ]);
        }

        $fileBlob = $file->get();
        if ($fileBlob === false || $fileBlob === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to read uploaded file.',
            ], 500);
        }

        $fileMime = $file->getClientMimeType() ?: ($file->getMimeType() ?: 'application/octet-stream');
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        $storedFiles = [[
            'original_name' => $fileName,
            'mime_type' => $fileMime,
            'size' => $fileSize,
            'stored_as' => 'blob',
        ]];

        $upload = DocumentUpload::create([
            'user_id' => $validated['user_id'],
            'pet_id' => $validated['pet_id'] ?? null,
            'record_type' => $validated['record_type'],
            'record_label' => $validated['record_label'],
            'source' => $validated['source'],
            'file_count' => 1,
            'files_json' => $storedFiles,
            'file_blob' => $fileBlob,
            'file_mime' => $fileMime,
            'file_name' => $fileName,
            'file_size' => $fileSize,
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
                'file_name' => $upload->file_name,
                'file_mime' => $upload->file_mime,
                'file_size' => $upload->file_size,
                'blob_url' => route('api.documents.blob', ['uploadId' => $upload->id]),
            ],
        ], 201);
    }

    /**
     * Fetch uploaded file blob by upload id.
     */
    public function blob(int $uploadId)
    {
        if (! $this->blobColumnsReady()) {
            return response()->json([
                'success' => false,
                'message' => 'document_uploads blob columns are missing. Please run migrations.',
            ], 500);
        }

        $upload = DocumentUpload::query()
            ->select(['id', 'file_blob', 'file_mime', 'file_name'])
            ->find($uploadId);

        if (! $upload || empty($upload->file_blob)) {
            return response()->json([
                'success' => false,
                'message' => 'Document blob not found.',
            ], 404);
        }

        $fileName = trim((string) ($upload->file_name ?: ('document-'.$upload->id)));
        $mime = trim((string) ($upload->file_mime ?: 'application/octet-stream'));

        return response($upload->file_blob, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
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

    private function blobColumnsReady(): bool
    {
        return Schema::hasTable('document_uploads')
            && Schema::hasColumn('document_uploads', 'file_blob')
            && Schema::hasColumn('document_uploads', 'file_mime')
            && Schema::hasColumn('document_uploads', 'file_name')
            && Schema::hasColumn('document_uploads', 'file_size');
    }
}
