<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_uploads')) {
            return;
        }

        if (! Schema::hasColumn('document_uploads', 'file_blob')) {
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE document_uploads ADD COLUMN file_blob LONGBLOB NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE document_uploads ADD COLUMN file_blob BYTEA NULL');
            } else {
                Schema::table('document_uploads', function (Blueprint $table) {
                    $table->binary('file_blob')->nullable();
                });
            }
        }

        Schema::table('document_uploads', function (Blueprint $table) {
            if (! Schema::hasColumn('document_uploads', 'file_mime')) {
                $table->string('file_mime', 120)->nullable()->after('file_blob');
            }
            if (! Schema::hasColumn('document_uploads', 'file_name')) {
                $table->string('file_name', 255)->nullable()->after('file_mime');
            }
            if (! Schema::hasColumn('document_uploads', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_uploads')) {
            return;
        }

        Schema::table('document_uploads', function (Blueprint $table) {
            if (Schema::hasColumn('document_uploads', 'file_size')) {
                $table->dropColumn('file_size');
            }
            if (Schema::hasColumn('document_uploads', 'file_name')) {
                $table->dropColumn('file_name');
            }
            if (Schema::hasColumn('document_uploads', 'file_mime')) {
                $table->dropColumn('file_mime');
            }
            if (Schema::hasColumn('document_uploads', 'file_blob')) {
                $table->dropColumn('file_blob');
            }
        });
    }
};
