<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('chats')->cascadeOnDelete();
            $table->string('message_key', 255)->unique();
            $table->string('from_jid', 255);
            $table->string('to_jid', 255)->nullable();
            $table->longText('message_text')->nullable();
            $table->enum('message_type', ['text', 'image', 'video', 'audio', 'document', 'sticker', 'location', 'contact'])->default('text');
            $table->string('media_url', 500)->nullable();
            $table->string('media_mime_type', 100)->nullable();
            $table->tinyInteger('is_from_me')->default(0);
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('timestamp');
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'error'])->default('sent');
            $table->tinyInteger('is_edited')->default(0);
            $table->tinyInteger('is_deleted')->default(0);
            $table->text('reactions')->nullable(); // JSON
            $table->string('quoted_message_id', 255)->nullable();
            $table->text('quoted_text')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->string('link_preview_title', 255)->nullable();
            $table->text('link_preview_description')->nullable();
            $table->string('link_preview_url', 255)->nullable();
            $table->longText('link_preview_thumbnail')->nullable();
            $table->text('remote_media_url')->nullable();
            $table->longText('message_raw')->nullable(); // JSON completo
            $table->timestamps();

            $table->index('timestamp');
            $table->index('from_jid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
