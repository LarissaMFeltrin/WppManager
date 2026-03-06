<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('jid', 255);
            $table->string('name', 255)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('profile_picture_url', 500)->nullable();
            $table->tinyInteger('is_business')->default(0);
            $table->tinyInteger('is_blocked')->default(0);
            $table->string('owner_jid', 255)->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'jid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
