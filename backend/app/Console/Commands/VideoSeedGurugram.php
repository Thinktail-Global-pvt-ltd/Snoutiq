<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\GurugramStripsSeeder;
use Illuminate\Console\Command;

class VideoSeedGurugram extends Command
{
    protected $signature = 'video:seed-gurugram';
    protected $description = 'Seed Gurugram geo strips for night video coverage';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => GurugramStripsSeeder::class]);
        $this->info('Seeded Gurugram geo strips.');
        return self::SUCCESS;
    }
}

