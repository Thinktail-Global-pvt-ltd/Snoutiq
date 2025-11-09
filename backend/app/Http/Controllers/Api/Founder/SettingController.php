<?php

namespace App\Http\Controllers\Api\Founder;

use App\Http\Requests\Founder\SettingUpdateRequest;
use App\Models\FounderSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends BaseController
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = FounderSetting::query()
            ->where('user_id', $user->getKey())
            ->first();

        $data = $this->mergeWithDefaults($settings?->data ?? []);

        return $this->success([
            'user' => [
                'id' => (string) $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role ?? 'Founder',
            ],
            'preferences' => [
                'notifications' => [
                    'enabled' => $data['notifications']['enabled'],
                ],
                'theme' => $data['theme'],
            ],
        ]);
    }

    public function update(SettingUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->payload();

        if (empty($payload)) {
            return $this->error('VALIDATION_ERROR', 'No settings provided to update.', 422);
        }

        $model = FounderSetting::query()->firstOrNew([
            'user_id' => $user->getKey(),
        ]);

        $existing = $this->mergeWithDefaults($model->data ?? []);
        $updated = array_replace_recursive($existing, $payload);

        $model->data = $updated;
        $model->save();

        $updatedKeys = [];
        if (array_key_exists('notifications', $payload)) {
            $updatedKeys[] = 'preferences.notifications.enabled';
        }
        if (array_key_exists('theme', $payload)) {
            $updatedKeys[] = 'preferences.theme';
        }

        return $this->success([
            'message' => 'Settings updated successfully',
            'updated' => $updatedKeys,
            'preferences' => [
                'notifications' => [
                    'enabled' => $updated['notifications']['enabled'],
                ],
                'theme' => $updated['theme'],
            ],
        ]);
    }

    private function mergeWithDefaults(array $data): array
    {
        $defaults = [
            'notifications' => [
                'enabled' => true,
            ],
            'theme' => 'light',
        ];

        return array_replace_recursive($defaults, $data);
    }
}

