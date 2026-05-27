<?php

namespace Database\Seeders;

use App\Models\AppVersion;
use Illuminate\Database\Seeder;

class AppVersionSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'app_key' => 'professional',
                'platform' => 'android',
                'min_supported_version' => '1.0.4',
                'latest_version' => '1.0.4',
                'force_update' => false,
                'store_url' => 'https://play.google.com/store/apps/details?id=com.snoutiq.professional',
                'title' => 'Update required',
                'message' => 'Please update your app to continue using SnoutIQ Professional.',
                'is_active' => true,
            ],
            [
                'app_key' => 'professional',
                'platform' => 'ios',
                'min_supported_version' => '1.0.4',
                'latest_version' => '1.0.4',
                'force_update' => false,
                'store_url' => 'https://apps.apple.com/app/idXXXXXXXXXX',
                'title' => 'Update required',
                'message' => 'Please update your app to continue using SnoutIQ Professional.',
                'is_active' => true,
            ],
            [
                'app_key' => 'pet-parent',
                'platform' => 'android',
                'min_supported_version' => '1.0.4',
                'latest_version' => '1.0.4',
                'force_update' => false,
                'store_url' => 'https://play.google.com/store/apps/details?id=com.petai.snoutiq&pcampaignid=web_share',
                'title' => 'Update required',
                'message' => 'Please update your app to continue using SnoutIQ.',
                'is_active' => true,
            ],
            [
                'app_key' => 'pet-parent',
                'platform' => 'ios',
                'min_supported_version' => '1.0.4',
                'latest_version' => '1.0.4',
                'force_update' => false,
                'store_url' => null,
                'title' => 'Update required',
                'message' => 'Please update your app to continue using SnoutIQ.',
                'is_active' => true,
            ],
        ];

        foreach ($defaults as $default) {
            AppVersion::query()->updateOrCreate(
                [
                    'app_key' => $default['app_key'],
                    'platform' => $default['platform'],
                ],
                $default
            );
        }
    }
}
