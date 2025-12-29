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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_type');
            $table->string('file_size');
            $table->text('file_url');
            $table->text('thumbnail_url');
            $table->integer('duration');
            $table->integer('width');
            $table->integer('height');
            $table->integer('uploaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
