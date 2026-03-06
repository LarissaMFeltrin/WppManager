<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('chat_id', 255); // JID: 5544999999999@s.whatsapp.net
            $table->string('chat_name', 255)->nullable();
            $table->enum('chat_type', ['individual', 'group', 'broadcast'])->default('individual');
            $table->tinyInteger('is_pinned')->default(0);
            $table->tinyInteger('is_archived')->default(0);
            $table->integer('unread_count')->default(0);
            $table->bigInteger('last_message_timestamp')->nullable();
            $table->string('owner_jid', 255)->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'chat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
