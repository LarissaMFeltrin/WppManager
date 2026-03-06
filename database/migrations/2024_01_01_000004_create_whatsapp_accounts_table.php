<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('phone_number', 20);
            $table->string('session_name', 255)->unique();
            $table->string('owner_jid', 255)->nullable();
            $table->tinyInteger('is_connected')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->integer('service_port')->nullable();
            $table->timestamp('last_connection')->nullable();
            $table->timestamp('last_full_sync')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
