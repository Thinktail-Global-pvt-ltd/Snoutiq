<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vet_feedback')) {
            Schema::table('vet_feedback', function (Blueprint $table) {
                if (!Schema::hasColumn('vet_feedback', 'channel_name')) {
                    $table->string('channel_name', 191)
                        ->nullable()
                        ->after('pet_id')
                        ->index();
                }
            });
        }

        if (Schema::hasTable('pet_feedback')) {
            Schema::table('pet_feedback', function (Blueprint $table) {
                if (!Schema::hasColumn('pet_feedback', 'channel_name')) {
                    $table->string('channel_name', 191)
                        ->nullable()
                        ->after('user_id')
                        ->index();
                }
            });
        }

        $this->backfillFeedbackChannels();
    }

    public function down(): void
    {
        if (Schema::hasTable('vet_feedback') && Schema::hasColumn('vet_feedback', 'channel_name')) {
            Schema::table('vet_feedback', function (Blueprint $table) {
                $table->dropColumn('channel_name');
            });
        }

        if (Schema::hasTable('pet_feedback') && Schema::hasColumn('pet_feedback', 'channel_name')) {
            Schema::table('pet_feedback', function (Blueprint $table) {
                $table->dropColumn('channel_name');
            });
        }
    }

    private function backfillFeedbackChannels(): void
    {
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $hasTransactionsTable = Schema::hasTable('transactions');
        $hasTransactionChannel = $hasTransactionsTable && Schema::hasColumn('transactions', 'channel_name');
        $hasTransactionCreated = $hasTransactionsTable && Schema::hasColumn('transactions', 'created_at');
        $hasTransactionId = $hasTransactionsTable && Schema::hasColumn('transactions', 'id');
        $hasTransactionDoctor = $hasTransactionsTable && Schema::hasColumn('transactions', 'doctor_id');
        $hasTransactionUser = $hasTransactionsTable && Schema::hasColumn('transactions', 'user_id');
        $hasTransactionPet = $hasTransactionsTable && Schema::hasColumn('transactions', 'pet_id');
        $hasTransactionType = $hasTransactionsTable && Schema::hasColumn('transactions', 'type');

        if (Schema::hasTable('vet_feedback') && Schema::hasColumn('vet_feedback', 'channel_name')) {
            $hasVetMeta = Schema::hasColumn('vet_feedback', 'meta');
            if ($hasVetMeta) {
                DB::statement("
                    UPDATE vet_feedback
                    SET channel_name = COALESCE(
                        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.channel_name')), ''),
                        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.channelName')), '')
                    )
                    WHERE (channel_name IS NULL OR channel_name = '')
                      AND meta IS NOT NULL
                      AND JSON_VALID(meta)
                ");
            }

            if (
                $hasTransactionChannel
                && $hasTransactionCreated
                && $hasTransactionId
                && $hasTransactionDoctor
                && $hasTransactionUser
                && $hasTransactionPet
                && Schema::hasColumn('vet_feedback', 'vet_id')
                && Schema::hasColumn('vet_feedback', 'user_id')
                && Schema::hasColumn('vet_feedback', 'pet_id')
                && Schema::hasColumn('vet_feedback', 'created_at')
            ) {
                $typeClause = $hasTransactionType
                    ? "AND t.type IN ('video_consult', 'excell_export_campaign')"
                    : '';

                DB::statement("
                    UPDATE vet_feedback vf
                    SET vf.channel_name = (
                        SELECT t.channel_name
                        FROM transactions t
                        WHERE t.channel_name IS NOT NULL
                          AND t.channel_name <> ''
                          {$typeClause}
                          AND t.doctor_id = vf.vet_id
                          AND (vf.user_id IS NULL OR t.user_id = vf.user_id)
                          AND (vf.pet_id IS NULL OR t.pet_id = vf.pet_id)
                        ORDER BY ABS(TIMESTAMPDIFF(SECOND, COALESCE(vf.created_at, NOW()), t.created_at)), t.id DESC
                        LIMIT 1
                    )
                    WHERE (vf.channel_name IS NULL OR vf.channel_name = '')
                ");
            }
        }

        if (Schema::hasTable('pet_feedback') && Schema::hasColumn('pet_feedback', 'channel_name')) {
            $hasPetMeta = Schema::hasColumn('pet_feedback', 'meta');
            if ($hasPetMeta) {
                DB::statement("
                    UPDATE pet_feedback
                    SET channel_name = COALESCE(
                        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.channel_name')), ''),
                        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.channelName')), '')
                    )
                    WHERE (channel_name IS NULL OR channel_name = '')
                      AND meta IS NOT NULL
                      AND JSON_VALID(meta)
                ");
            }

            if (
                $hasTransactionChannel
                && $hasTransactionCreated
                && $hasTransactionId
                && $hasTransactionDoctor
                && $hasTransactionUser
                && $hasTransactionPet
                && Schema::hasColumn('pet_feedback', 'vet_id')
                && Schema::hasColumn('pet_feedback', 'user_id')
                && Schema::hasColumn('pet_feedback', 'pet_id')
                && Schema::hasColumn('pet_feedback', 'created_at')
            ) {
                $typeClause = $hasTransactionType
                    ? "AND t.type IN ('video_consult', 'excell_export_campaign')"
                    : '';

                DB::statement("
                    UPDATE pet_feedback pf
                    SET pf.channel_name = (
                        SELECT t.channel_name
                        FROM transactions t
                        WHERE t.channel_name IS NOT NULL
                          AND t.channel_name <> ''
                          {$typeClause}
                          AND t.pet_id = pf.pet_id
                          AND (pf.vet_id IS NULL OR t.doctor_id = pf.vet_id)
                          AND (pf.user_id IS NULL OR t.user_id = pf.user_id)
                        ORDER BY ABS(TIMESTAMPDIFF(SECOND, COALESCE(pf.created_at, NOW()), t.created_at)), t.id DESC
                        LIMIT 1
                    )
                    WHERE (pf.channel_name IS NULL OR pf.channel_name = '')
                ");
            }
        }
    }
};
