<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
            $table->string('claim_token', 64)->nullable()->unique()->after('public_id');
            $table->string('status', 32)->default('draft')->index()->after('claim_token');
            $table->unsignedBigInteger('owner_user_id')->nullable()->index()->after('status');
            $table->unsignedBigInteger('draft_created_by_user_id')->nullable()->index()->after('owner_user_id');
            $table->timestamp('draft_expires_at')->nullable()->after('draft_created_by_user_id');
            $table->timestamp('claimed_at')->nullable()->after('draft_expires_at');
            $table->string('qr_code_path')->nullable()->after('claimed_at');
        });

        // Backfill existing rows with stable identifiers and mark them active
        DB::table('vet_registerations_temp')
            ->select('id', 'public_id')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $publicId = $row->public_id ?: Str::ulid()->toBase32();
                    DB::table('vet_registerations_temp')
                        ->where('id', $row->id)
                        ->update([
                            'public_id'   => $publicId,
                            'status'      => 'active',
                            'claim_token' => null,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vet_registerations_temp', function (Blueprint $table) {
            $table->dropColumn([
                'qr_code_path',
                'claimed_at',
                'draft_expires_at',
                'draft_created_by_user_id',
                'owner_user_id',
                'status',
                'claim_token',
                'public_id',
            ]);
        });
    }
};
