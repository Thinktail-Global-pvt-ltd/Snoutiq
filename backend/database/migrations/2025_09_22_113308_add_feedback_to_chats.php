<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_feedback_to_chats.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('chats', function (Blueprint $table) {
            $table->tinyInteger('feedback')->nullable()->after('emergency_status'); // 1 or 0
        });
    }

    public function down(): void {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('feedback');
        });
    }
};