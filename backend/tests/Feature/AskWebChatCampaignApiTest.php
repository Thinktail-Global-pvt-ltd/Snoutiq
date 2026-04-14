<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AskWebChatCampaignApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('web_chat_campaign');
        Schema::create('web_chat_campaign', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->unsignedSmallInteger('turn')->default(1)->index();
            $table->string('routing', 30)->nullable()->index();
            $table->string('severity', 30)->nullable()->index();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('pet_name', 120)->nullable();
            $table->string('species', 30)->nullable();
            $table->string('breed', 120)->nullable();
            $table->string('location', 120)->nullable();
            $table->text('user_message')->nullable();
            $table->longText('assistant_message')->nullable();
            $table->longText('request_payload_json')->nullable();
            $table->longText('response_payload_json')->nullable();
            $table->longText('state_payload_json')->nullable();
            $table->timestamps();
        });
    }

    public function test_create_room_writes_to_web_chat_campaign(): void
    {
        $response = $this->postJson('/api/ask/chat-rooms/new', [
            'user_id' => 123,
            'title' => 'Dog vomiting since morning',
            'pet_id' => 456,
            'pet_name' => 'Bruno',
            'pet_breed' => 'Labrador',
            'pet_location' => 'Gurgaon',
            'species' => 'dog',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('name', 'Dog vomiting since morning');

        $token = $response->json('chat_room_token');
        $this->assertNotEmpty($token);

        $row = DB::table('web_chat_campaign')->where('session_id', $token)->first();

        $this->assertNotNull($row);
        $this->assertSame(123, (int) $row->user_id);
        $this->assertSame(456, (int) $row->pet_id);
        $this->assertSame(0, (int) $row->turn);
        $this->assertSame('Bruno', $row->pet_name);
    }

    public function test_list_rooms_and_history_read_from_web_chat_campaign(): void
    {
        DB::table('web_chat_campaign')->insert([
            [
                'session_id' => 'room_abc',
                'user_id' => 123,
                'pet_id' => 456,
                'turn' => 0,
                'routing' => 'new_room',
                'severity' => null,
                'score' => 0,
                'pet_name' => 'Bruno',
                'species' => 'dog',
                'breed' => 'Labrador',
                'location' => 'Gurgaon',
                'user_message' => null,
                'assistant_message' => null,
                'request_payload_json' => json_encode(['title' => 'Dog vomiting since morning']),
                'response_payload_json' => json_encode(['event' => 'room_created']),
                'state_payload_json' => json_encode(['history' => []]),
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'session_id' => 'room_abc',
                'user_id' => 123,
                'pet_id' => 456,
                'turn' => 1,
                'routing' => 'video_consult',
                'severity' => 'moderate',
                'score' => 6,
                'pet_name' => 'Bruno',
                'species' => 'dog',
                'breed' => 'Labrador',
                'location' => 'Gurgaon',
                'user_message' => 'My dog is vomiting',
                'assistant_message' => 'A video consult is recommended.',
                'request_payload_json' => json_encode(['message' => 'My dog is vomiting']),
                'response_payload_json' => json_encode(['message' => 'A video consult is recommended.']),
                'state_payload_json' => json_encode(['history' => []]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $roomsResponse = $this->getJson('/api/ask/chat/listRooms?user_id=123');

        $roomsResponse->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('rooms.0.chat_room_token', 'room_abc')
            ->assertJsonPath('rooms.0.name', 'Dog vomiting since morning');

        $historyResponse = $this->getJson('/api/ask/chat-rooms/room_abc/chats?user_id=123&sort=asc');

        $historyResponse->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('room.chat_room_token', 'room_abc')
            ->assertJsonPath('count', 1)
            ->assertJsonPath('chats.0.question', 'My dog is vomiting')
            ->assertJsonPath('chats.0.answer', 'A video consult is recommended.');
    }

    public function test_delete_room_removes_all_matching_session_rows(): void
    {
        DB::table('web_chat_campaign')->insert([
            [
                'session_id' => 'room_delete',
                'user_id' => 123,
                'pet_id' => 456,
                'turn' => 1,
                'routing' => 'video_consult',
                'score' => 6,
                'user_message' => 'My dog is vomiting',
                'assistant_message' => 'A video consult is recommended.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'session_id' => 'room_delete',
                'user_id' => 123,
                'pet_id' => 456,
                'turn' => 2,
                'routing' => 'video_consult',
                'score' => 7,
                'user_message' => 'He is also weak',
                'assistant_message' => 'Please consult soon.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'session_id' => 'room_delete',
                'user_id' => 999,
                'pet_id' => 888,
                'turn' => 1,
                'routing' => 'monitor',
                'score' => 2,
                'user_message' => 'Other user message',
                'assistant_message' => 'Other user answer.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->deleteJson('/api/ask/chat-rooms/room_delete');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('deleted.chat_room_token', 'room_delete')
            ->assertJsonPath('deleted.rows_deleted', 3);

        $this->assertDatabaseMissing('web_chat_campaign', [
            'session_id' => 'room_delete',
            'user_id' => 123,
        ]);
        $this->assertDatabaseMissing('web_chat_campaign', [
            'session_id' => 'room_delete',
            'user_id' => 999,
        ]);
    }
}
