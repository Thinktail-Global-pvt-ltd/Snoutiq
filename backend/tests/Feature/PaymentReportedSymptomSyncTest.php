<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class PaymentReportedSymptomSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('pet_suggested_disease_logs');
        Schema::dropIfExists('pets');

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->integer('pet_age')->nullable();
            $table->string('pet_gender')->nullable();
            $table->text('reported_symptom')->nullable();
            $table->string('suggested_disease')->nullable();
            $table->string('health_state')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('pet_suggested_disease_logs');
        Schema::dropIfExists('pets');

        parent::tearDown();
    }

    public function test_payment_notes_sync_reported_symptom_to_pet_before_transaction_log_snapshot(): void
    {
        DB::table('pets')->insert([
            'id' => 910,
            'user_id' => 710,
            'name' => 'Sheru',
            'breed' => 'Indie',
            'reported_symptom' => 'old symptom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = app(PaymentController::class);
        $request = Request::create('/api/create-order', 'POST', [
            'pet_id' => 910,
            'symptoms' => ['Limping after a walk', 'not eating well'],
        ]);

        $notes = $this->invokeProtected($controller, 'mergeClientNotes', [$request, []]);
        $this->invokeProtected($controller, 'syncPaymentReportedSymptom', [
            ['pet_id' => 910],
            $notes,
            'test.payment-sync',
        ]);

        $this->assertDatabaseHas('pets', [
            'id' => 910,
            'reported_symptom' => 'Limping after a walk, not eating well',
        ]);
    }

    private function invokeProtected(object $object, string $method, array $args = [])
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }
}
