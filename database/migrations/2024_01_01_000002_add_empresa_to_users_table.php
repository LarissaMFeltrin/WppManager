<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresas')->nullOnDelete();
            $table->string('auth_token')->nullable()->unique()->after('password');
            $table->timestamp('token_expires_at')->nullable()->after('auth_token');
            $table->enum('role', ['admin', 'agent', 'supervisor'])->default('agent')->after('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn(['empresa_id', 'auth_token', 'token_expires_at', 'role']);
        });
    }
};
