<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VetRegisterationTemp;
use Illuminate\Support\Str;

class BackfillVetTempSlugs extends Command
{
    protected $signature = 'vet:backfill-slugs {--dry : Show what would change without saving}';
    protected $description = 'Backfill slug for VetRegisterationTemp where name is null using email prefix';

    public function handle(): int
    {
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
                    // keep simple as requested, but normalize to slug-ish string
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
        return self::SUCCESS;
    }
}

