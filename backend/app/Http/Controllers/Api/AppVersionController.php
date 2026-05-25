<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    private const APP_KEY_PROFESSIONAL = 'professional';
    private const DEFAULT_TITLE = 'Update required';
    private const DEFAULT_MESSAGE = 'Please update your app to continue using SnoutIQ Professional.';

    public function professional(Request $request)
    {
        $versions = AppVersion::query()
            ->where('app_key', self::APP_KEY_PROFESSIONAL)
            ->where('is_active', true)
            ->whereIn('platform', ['android', 'ios'])
            ->get()
            ->keyBy('platform');

        $this->bumpLatestVersionFromRequest($request, $versions);

        $android = $versions->get('android');
        $ios = $versions->get('ios');
        $messageSource = $android ?: $ios;

        return response()->json([
            'success' => true,
            'data' => [
                'android' => $android ? $this->publicPlatformPayload($android) : null,
                'ios' => $ios ? $this->publicPlatformPayload($ios) : null,
                'title' => $messageSource?->title ?: self::DEFAULT_TITLE,
                'message' => $messageSource?->message ?: self::DEFAULT_MESSAGE,
            ],
        ]);
    }

    public function adminProfessional()
    {
        $versions = AppVersion::query()
            ->where('app_key', self::APP_KEY_PROFESSIONAL)
            ->orderBy('platform')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $versions->map(fn (AppVersion $version) => $this->adminPayload($version))->values(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $version = AppVersion::query()->find($id);

        if (! $version) {
            return response()->json([
                'success' => false,
                'message' => 'App version not found.',
            ], 404);
        }

        $validated = $request->validate([
            'min_supported_version' => ['required', 'string', 'max:50'],
            'latest_version' => ['required', 'string', 'max:50'],
            'force_update' => ['required', 'boolean'],
            'store_url' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $version->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'App version updated successfully.',
            'data' => $this->adminPayload($version->refresh()),
        ]);
    }

    private function publicPlatformPayload(AppVersion $version): array
    {
        return [
            'min_supported_version' => $version->min_supported_version,
            'latest_version' => $version->latest_version,
            'force_update' => $version->force_update,
            'store_url' => $version->store_url,
        ];
    }

    private function bumpLatestVersionFromRequest(Request $request, $versions): void
    {
        $platform = strtolower((string) ($request->query('platform') ?: $request->header('X-App-Platform')));
        $currentVersion = trim((string) (
            $request->query('current_version')
            ?: $request->query('app_version')
            ?: $request->header('X-App-Version')
        ));

        if (! in_array($platform, ['android', 'ios'], true) || ! $this->isVersionString($currentVersion)) {
            return;
        }

        $version = $versions->get($platform);
        if (! $version || version_compare($currentVersion, $version->latest_version, '<=')) {
            return;
        }

        $version->latest_version = $currentVersion;
        $version->save();
    }

    private function isVersionString(string $version): bool
    {
        return $version !== ''
            && strlen($version) <= 50
            && preg_match('/^[0-9]+(?:\.[0-9]+){0,4}$/', $version) === 1;
    }

    private function adminPayload(AppVersion $version): array
    {
        return [
            'id' => $version->id,
            'app_key' => $version->app_key,
            'platform' => $version->platform,
            'min_supported_version' => $version->min_supported_version,
            'latest_version' => $version->latest_version,
            'force_update' => $version->force_update,
            'store_url' => $version->store_url,
            'title' => $version->title,
            'message' => $version->message,
            'is_active' => $version->is_active,
            'created_at' => $version->created_at,
            'updated_at' => $version->updated_at,
        ];
    }
}
