<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicCapturedTransactionsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('amount_paise')->default(0);
            $table->string('status')->default('pending');
            $table->string('type')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();
        });
    }

    public function test_public_page_shows_only_captured_non_one_rupee_transactions_with_existing_users(): void
    {
        DB::table('users')->insert([
            ['id' => 10, 'name' => 'Shown User', 'email' => 'shown@example.com', 'phone' => '9999999999', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'One Rupee User', 'email' => null, 'phone' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => 'Pending User', 'email' => null, 'phone' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => 'Near 399 User', 'email' => null, 'phone' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'name' => 'Inclusive GST User', 'email' => null, 'phone' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'name' => 'Gross 471 User', 'email' => null, 'phone' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 16, 'name' => 'Gross 589 User', 'email' => null, 'phone' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('transactions')->insert([
            ['id' => 1, 'user_id' => 10, 'amount_paise' => 49900, 'status' => 'captured', 'type' => 'video_consult', 'payment_method' => 'upi', 'reference' => 'pay_shown', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'user_id' => 11, 'amount_paise' => 100, 'status' => 'captured', 'type' => 'video_consult', 'payment_method' => 'upi', 'reference' => 'pay_one_rupee', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'user_id' => 12, 'amount_paise' => 49900, 'status' => 'pending', 'type' => 'video_consult', 'payment_method' => 'upi', 'reference' => 'pay_pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'user_id' => 999, 'amount_paise' => 49900, 'status' => 'captured', 'type' => 'video_consult', 'payment_method' => 'upi', 'reference' => 'pay_missing_user', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'user_id' => 13, 'amount_paise' => 39900, 'status' => 'captured', 'type' => 'video_consult', 'payment_method' => 'upi', 'reference' => 'pay_near_399', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'user_id' => 14, 'amount_paise' => 76600, 'status' => 'captured', 'type' => 'video_consult', 'payment_method' => 'upi', 'reference' => 'pay_766', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'user_id' => 15, 'amount_paise' => 47100, 'status' => 'captured', 'type' => 'video_consult', 'payment_method' => 'upi', 'reference' => 'pay_471', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'user_id' => 16, 'amount_paise' => 58900, 'status' => 'captured', 'type' => 'video_consult', 'payment_method' => 'upi', 'reference' => 'pay_589', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->get('/captured-transactions');

        $response->assertOk();
        $response->assertSee('Shown User');
        $response->assertSee('pay_shown');
        $response->assertSee('Matched ₹499.00');
        $response->assertSee('Matched ₹399.00');
        $response->assertSee('Matched ₹766.00');
        $response->assertSee('Matched ₹471.00');
        $response->assertSee('Matched ₹589.00');
        $response->assertSee('Base: ₹399.00');
        $response->assertSee('Base: ₹499.00');
        $response->assertSee('GST @ 18%');
        $response->assertSee('GST not added');
        $response->assertSee('GST included');
        $response->assertSee('Amount with GST');
        $response->assertSee('gst-included-row');
        $response->assertSee('gst-not-added-row');
        $response->assertSee('Preview');
        $response->assertSee('Download');
        $response->assertSee('Download all invoices');
        $response->assertSee('invoice-download-link');
        $response->assertSee('/captured-transactions/1/invoice', false);
        $response->assertDontSee('pay_one_rupee');
        $response->assertDontSee('pay_pending');
        $response->assertDontSee('pay_missing_user');
    }

    public function test_invoice_preview_streams_pdf_for_green_and_red_rows(): void
    {
        DB::table('users')->insert([
            ['id' => 10, 'name' => 'Green User', 'email' => 'green@example.com', 'phone' => '9999999999', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Red User', 'email' => 'red@example.com', 'phone' => '8888888888', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('transactions')->insert([
            ['id' => 10, 'user_id' => 10, 'amount_paise' => 58900, 'status' => 'captured', 'type' => 'video_consult', 'payment_method' => 'upi', 'reference' => 'pay_green', 'created_at' => '2026-03-31 23:59:59', 'updated_at' => now()],
            ['id' => 11, 'user_id' => 11, 'amount_paise' => 64800, 'status' => 'captured', 'type' => 'video_consult', 'payment_method' => 'upi', 'reference' => 'pay_red', 'created_at' => '2026-04-01 00:00:00', 'updated_at' => now()],
        ]);

        $greenResponse = $this->get('/captured-transactions/10/invoice');
        $redResponse = $this->get('/captured-transactions/11/invoice?download=1');

        $greenResponse->assertOk();
        $greenResponse->assertHeader('Content-Type', 'application/pdf');
        $greenResponse->assertHeader('Content-Disposition', 'inline; filename="0000-001-10.pdf"');

        $redResponse->assertOk();
        $redResponse->assertHeader('Content-Type', 'application/pdf');
        $redResponse->assertHeader('Content-Disposition', 'attachment; filename="0000-002-11.pdf"');
    }
}
