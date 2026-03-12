<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela para vincular diferentes JIDs (LID e número) do mesmo contato
     */
    public function up(): void
    {
        Schema::create('contact_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->foreignId('primary_chat_id')->constrained('chats')->cascadeOnDelete();
            $table->string('alias_jid', 255); // JID alternativo (LID ou número)
            $table->timestamps();

            $table->unique(['account_id', 'alias_jid']);
            $table->index('alias_jid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_aliases');
    }
};
