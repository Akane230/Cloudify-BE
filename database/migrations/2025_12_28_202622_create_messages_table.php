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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')
                  ->constrained(table: 'users', column: 'id')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            $table->foreignId('conversation_id');
            $table->string('message_type');
            $table->text('content');
            $table->text('media_url');
            $table->foreignId('reply_to_message_id')
                  ->constrained(table: 'messages', column: 'id')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            $table->boolean('is_edited');
            $table->boolean('is_deleted');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
