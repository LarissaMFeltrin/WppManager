<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendente_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atendente_id')->constrained('atendentes')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['atendente_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendente_account');
    }
};
