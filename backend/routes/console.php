<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\VetRegisterationTemp;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backfill slugs for VetRegisterationTemp where name is null.
Artisan::command('vet:backfill-slugs {--dry}', function () {
    $dry = (bool) $this->option('dry');
    $updated = 0; $skipped = 0; $total = 0;

    VetRegisterationTemp::whereNull('name')
        ->orderBy('id')
        ->chunkById(200, function ($rows) use (&$updated, &$skipped, &$total, $dry) {
            foreach ($rows as $row) {
                $total++;
                $email = trim((string) $row->email);
                if ($email === '' || !str_contains($email, '@')) { $skipped++; continue; }

                $local = Str::before($email, '@');
                $base = Str::slug($local);
                if ($base === '') { $skipped++; continue; }

                $slug = $base; $i = 1;
                while (VetRegisterationTemp::where('slug', $slug)->where('id', '!=', $row->id)->exists()) {
                    $slug = $base.'-'.$i++;
                }

                if ($dry) {
                    $this->line("#{$row->id} {$email} -> {$slug} (dry)");
                    continue;
                }

                $row->slug = $slug;
                $row->save();
                $updated++;
            }
        });

    $this->info("Processed: {$total}, Updated: {$updated}, Skipped: {$skipped}");
})->purpose('Backfill slug from email prefix for VetRegisterationTemp rows with null name');
