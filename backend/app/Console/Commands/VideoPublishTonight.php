<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SlotPublisherService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VideoPublishTonight extends Command
{
    protected $signature = 'video:publish-tonight';
    protected $description = 'Publish tonight\'s night-band video slots for all strips';

    public function handle(SlotPublisherService $publisher): int
    {
        $todayIST = Carbon::now('Asia/Kolkata')->startOfDay();
        $publisher->publishNightSlots($todayIST);
        $this->info('Published tonight\'s slots.');
        return self::SUCCESS;
    }
}

