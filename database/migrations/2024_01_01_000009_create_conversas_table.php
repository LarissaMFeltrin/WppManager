<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversas', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_numero', 100);
            $table->string('cliente_nome', 100)->nullable();
            $table->foreignId('chat_id')->nullable()->constrained('chats')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('whatsapp_accounts')->nullOnDelete();
            $table->foreignId('atendente_id')->nullable()->constrained('atendentes')->nullOnDelete();
            $table->foreignId('devolvida_por')->nullable()->constrained('atendentes')->nullOnDelete();
            $table->enum('status', ['aguardando', 'em_atendimento', 'finalizada'])->default('aguardando');
            $table->tinyInteger('bloqueada')->default(0);
            $table->text('notas')->nullable();
            $table->timestamp('iniciada_em')->useCurrent();
            $table->timestamp('atendida_em')->nullable();
            $table->timestamp('finalizada_em')->nullable();
            $table->timestamp('ultima_msg_em')->nullable();
            $table->timestamp('cliente_aguardando_desde')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('cliente_numero');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversas');
    }
};
