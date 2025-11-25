<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class S3RecordingController extends Controller
{
    private array $allowedExtensions = [
        'mp4',
        'webm',
        'mov',
        'mkv',
        'm4a',
        'mp3',
        'wav',
        'aac',
        'ogg',
    ];

    public function index(): View
    {
        $disk = Storage::disk('s3');
        $paths = collect($disk->allFiles('recordings'));

        $files = $paths
            ->filter(fn (string $path) => $this->shouldListFile($path))
            ->map(fn (string $path) => $this->mapFileMetadata($disk, $path))
            ->sortByDesc(fn (array $file) => $file['last_modified']?->getTimestamp() ?? 0)
            ->values();

        return view('s3-recordings.index', [
            'files' => $files,
            'bucket' => config('filesystems.disks.s3.bucket') ?? 's3',
        ]);
    }

    private function shouldListFile(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === '') {
            return true;
        }

        return in_array($extension, $this->allowedExtensions, true);
    }

    private function mapFileMetadata($disk, string $path): array
    {
        [$size, $lastModified] = $this->gatherMetadata($disk, $path);

        return [
            'path' => $path,
            'name' => basename($path),
            'url' => $disk->url($path),
            'size' => $size,
            'last_modified' => $lastModified,
        ];
    }

    private function gatherMetadata($disk, string $path): array
    {
        $size = null;
        $lastModified = null;

        try {
            $size = $disk->size($path);
        } catch (\Throwable) {
            //
        }

        try {
            $timestamp = $disk->lastModified($path);
            $lastModified = $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
        } catch (\Throwable) {
            //
        }

        return [$size, $lastModified];
    }
}
