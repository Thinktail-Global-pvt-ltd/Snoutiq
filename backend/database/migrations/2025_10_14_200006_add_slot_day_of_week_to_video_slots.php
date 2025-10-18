<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('video_slots', function (Blueprint $table) {
            $table->string('slot_day_of_week', 16)->nullable()->after('slot_date');
            $table->index('slot_day_of_week');
        });

        DB::table('video_slots')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                if ($row->slot_day_of_week) {
                    continue;
                }

                $slotDate = $row->slot_date;
                $hour24   = (int) $row->hour_24;

                if (!$slotDate) {
                    continue;
                }

                $utc = CarbonImmutable::createFromFormat(
                    'Y-m-d H:i:s',
                    sprintf('%s %02d:00:00', $slotDate, $hour24),
                    'UTC'
                );

                $ist = $utc->setTimezone('Asia/Kolkata');
                if ($ist->hour <= 6) {
                    $ist = $ist->subDay();
                }

                $day = strtolower($ist->format('l'));

                DB::table('video_slots')->where('id', $row->id)->update([
                    'slot_day_of_week' => $day,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('video_slots', function (Blueprint $table) {
            $table->dropIndex('video_slots_slot_day_of_week_index');
            $table->dropColumn('slot_day_of_week');
        });
    }
};
