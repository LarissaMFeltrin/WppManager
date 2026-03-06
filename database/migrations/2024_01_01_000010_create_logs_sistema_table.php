<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_sistema', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['webhook', 'api', 'atendimento', 'erro', 'info'])->default('info');
            $table->enum('nivel', ['debug', 'info', 'warning', 'error', 'critical'])->default('info');
            $table->text('mensagem')->nullable();
            $table->longText('dados')->nullable(); // JSON
            $table->string('ip_origem', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('criada_em')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_sistema');
    }
};
