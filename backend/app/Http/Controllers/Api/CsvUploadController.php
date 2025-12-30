<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CsvUploadController extends Controller
{
    public function form()
    {
        return view('clinic.csv-upload');
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->handleUpload($request);

        return response()->json([
            'success' => true,
            'message' => 'CSV uploaded successfully.',
            'path' => $payload['path'],
            'absolute_path' => $payload['absolute_path'],
            'original_name' => $payload['original_name'],
            'size_bytes' => $payload['size_bytes'],
        ], 201);
    }

    public function storeWeb(Request $request)
    {
        $payload = $this->handleUpload($request);

        return back()
            ->with('status', 'CSV uploaded successfully.')
            ->with('upload', $payload);
    }

    public function show(Request $request)
    {
        $encoded = $request->query('path');
        $path = $encoded ? base64_decode($encoded, true) : null;

        if (!$path || !Str::startsWith($path, 'csv-uploads/')) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $absolute = Storage::disk('local')->path($path);
        $mime = mime_content_type($absolute) ?: 'text/csv';

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * Shared upload handler used by both web + API.
     */
    protected function handleUpload(Request $request): array
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // up to 10 MB
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];

        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'csv-upload';
        $extension = $file->getClientOriginalExtension() ?: 'csv';

        $safeBase = Str::slug($baseName, '-');
        $timestamp = now()->format('Ymd_His');
        $storedName = "{$timestamp}-{$safeBase}-" . Str::lower(Str::random(6)) . ".{$extension}";

        $relativePath = $file->storeAs('csv-uploads', $storedName, 'local');
        $absolutePath = Storage::disk('local')->path($relativePath);

        return [
            'path' => $relativePath,
            'absolute_path' => $absolutePath,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
        ];
    }
}
