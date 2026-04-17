<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserBulkDeletePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->string('city')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_admin_user_bulk_delete_page_lists_all_users_and_supports_search(): void
    {
        User::query()->create([
            'name' => 'Soumita Chakraborty',
            'email' => 'soumita@example.com',
            'phone' => '917896389114',
            'role' => 'pet_owner',
            'city' => 'Nagao',
            'password' => 'secret',
        ]);

        User::query()->create([
            'name' => 'Test Patient',
            'email' => 'test@example.com',
            'phone' => '911111111111',
            'role' => 'pet_owner',
            'city' => 'Delhi',
            'password' => 'secret',
        ]);

        $response = $this->withSession([
            'is_admin' => true,
            'admin_email' => (string) config('admin.email', 'admin@snoutiq.com'),
            'role' => 'admin',
        ])->get(route('admin.users.bulk-delete'));

        $response->assertOk();
        $response->assertSee('User Bulk Delete');
        $response->assertSee('Soumita Chakraborty');
        $response->assertSee('Test Patient');
        $response->assertSee('Delete Selected Users');

        $searchResponse = $this->withSession([
            'is_admin' => true,
            'admin_email' => (string) config('admin.email', 'admin@snoutiq.com'),
            'role' => 'admin',
        ])->get(route('admin.users.bulk-delete', ['q' => 'Soumita']));

        $searchResponse->assertOk();
        $searchResponse->assertSee('Soumita Chakraborty');
        $searchResponse->assertDontSee('Test Patient');
    }

    public function test_admin_can_bulk_delete_selected_users(): void
    {
        $firstUser = User::query()->create([
            'name' => 'Delete Me One',
            'email' => 'delete-one@example.com',
            'phone' => '910000000001',
            'role' => 'pet_owner',
            'city' => 'Mumbai',
            'password' => 'secret',
        ]);

        $secondUser = User::query()->create([
            'name' => 'Delete Me Two',
            'email' => 'delete-two@example.com',
            'phone' => '910000000002',
            'role' => 'pet_owner',
            'city' => 'Pune',
            'password' => 'secret',
        ]);

        $remainingUser = User::query()->create([
            'name' => 'Keep Me',
            'email' => 'keep@example.com',
            'phone' => '910000000003',
            'role' => 'pet_owner',
            'city' => 'Jaipur',
            'password' => 'secret',
        ]);

        $response = $this->withSession([
            'is_admin' => true,
            'admin_email' => (string) config('admin.email', 'admin@snoutiq.com'),
            'role' => 'admin',
        ])->post(route('admin.users.bulk-delete.destroy'), [
            'user_ids' => [$firstUser->id, $secondUser->id],
        ]);

        $response->assertRedirect(route('admin.users.bulk-delete'));
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['id' => $firstUser->id]);
        $this->assertDatabaseMissing('users', ['id' => $secondUser->id]);
        $this->assertDatabaseHas('users', ['id' => $remainingUser->id]);
    }
}
