<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminPanelController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class AdminExcellExportTransactionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('transactions');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('vet_registerations_temp');

        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_email')->nullable();
            $table->string('doctor_mobile')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->string('pet_type')->nullable();
            $table->text('reported_symptom')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedInteger('amount_paise')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function test_excell_export_transactions_query_includes_rows_without_existing_clinic(): void
    {
        DB::table('users')->insert([
            'id' => 10,
            'name' => 'Rahul Sharma',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 20,
            'user_id' => 10,
            'name' => 'Bruno',
            'reported_symptom' => 'Coughing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            'id' => 1105,
            'clinic_id' => 999999,
            'doctor_id' => null,
            'user_id' => 10,
            'pet_id' => 20,
            'amount_paise' => 100,
            'status' => 'pending',
            'type' => 'excell_export_campaign',
            'payment_method' => 'manual',
            'reference' => 'missing-clinic-row',
            'metadata' => json_encode(['order_type' => 'excell_export_campaign']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $method = new ReflectionMethod(AdminPanelController::class, 'excellExportTransactionsQuery');
        $method->setAccessible(true);

        $rows = $method->invoke(app(AdminPanelController::class))->get();

        $this->assertCount(1, $rows);
        $this->assertSame(1105, $rows->first()->id);
        $this->assertNull($rows->first()->clinic);
    }
}
