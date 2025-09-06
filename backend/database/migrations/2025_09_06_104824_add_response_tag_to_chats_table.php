<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            // ✅ Add response_tag column after answer
            if (!Schema::hasColumn('chats', 'response_tag')) {
                $table->string('response_tag')->nullable()->after('answer');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if (Schema::hasColumn('chats', 'response_tag')) {
                $table->dropColumn('response_tag');
            }
        });
    }
};
