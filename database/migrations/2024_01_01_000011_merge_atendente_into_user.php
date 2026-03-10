<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adicionar campos de atendente ao users
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status_atendimento', ['online', 'offline', 'ocupado'])->default('offline')->after('role');
            $table->integer('max_conversas')->default(5)->after('status_atendimento');
            $table->integer('conversas_ativas')->default(0)->after('max_conversas');
            $table->timestamp('ultimo_acesso')->nullable()->after('conversas_ativas');
        });

        // 2. Criar nova tabela pivot user_account
        Schema::create('user_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'account_id']);
        });

        // 3. Migrar dados de atendente_account para user_account (se existir dados)
        if (Schema::hasTable('atendente_account') && Schema::hasTable('atendentes')) {
            DB::statement("
                INSERT INTO user_account (user_id, account_id, created_at, updated_at)
                SELECT a.user_id, aa.account_id, aa.created_at, aa.created_at
                FROM atendente_account aa
                JOIN atendentes a ON a.id = aa.atendente_id
                WHERE a.user_id IS NOT NULL
            ");
        }

        // 4. Migrar dados dos atendentes para users (atualizar campos)
        if (Schema::hasTable('atendentes')) {
            DB::statement("
                UPDATE users u
                JOIN atendentes a ON a.user_id = u.id
                SET u.status_atendimento = a.status,
                    u.max_conversas = a.max_conversas,
                    u.conversas_ativas = a.conversas_ativas,
                    u.ultimo_acesso = a.ultimo_acesso
            ");
        }

        // 5. Adicionar novas colunas em conversas para user_id
        Schema::table('conversas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('account_id')->constrained('users')->nullOnDelete();
            $table->foreignId('devolvida_por_user_id')->nullable()->after('devolvida_por')->constrained('users')->nullOnDelete();
        });

        // 6. Migrar dados de atendente_id para user_id em conversas
        if (Schema::hasTable('atendentes')) {
            DB::statement("
                UPDATE conversas c
                JOIN atendentes a ON a.id = c.atendente_id
                SET c.user_id = a.user_id
                WHERE a.user_id IS NOT NULL
            ");

            DB::statement("
                UPDATE conversas c
                JOIN atendentes a ON a.id = c.devolvida_por
                SET c.devolvida_por_user_id = a.user_id
                WHERE a.user_id IS NOT NULL
            ");
        }

        // 7. Remover colunas antigas de conversas
        Schema::table('conversas', function (Blueprint $table) {
            $table->dropForeign(['atendente_id']);
            $table->dropForeign(['devolvida_por']);
            $table->dropColumn(['atendente_id', 'devolvida_por']);
        });

        // 8. Renomear colunas
        Schema::table('conversas', function (Blueprint $table) {
            $table->renameColumn('user_id', 'atendente_id');
            $table->renameColumn('devolvida_por_user_id', 'devolvida_por');
        });

        // 9. Remover tabela pivot antiga
        Schema::dropIfExists('atendente_account');

        // 10. Remover tabela atendentes
        Schema::dropIfExists('atendentes');
    }

    public function down(): void
    {
        // Recriar tabela atendentes
        Schema::create('atendentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nome', 100);
            $table->string('email', 100)->unique();
            $table->string('senha', 255);
            $table->enum('status', ['online', 'offline', 'ocupado'])->default('offline');
            $table->integer('max_conversas')->default(5);
            $table->integer('conversas_ativas')->default(0);
            $table->timestamp('ultimo_acesso')->nullable();
            $table->timestamps();
        });

        // Recriar tabela pivot
        Schema::create('atendente_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atendente_id')->constrained('atendentes')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['atendente_id', 'account_id']);
        });

        // Alterar conversas de volta
        Schema::table('conversas', function (Blueprint $table) {
            $table->renameColumn('atendente_id', 'user_id');
            $table->renameColumn('devolvida_por', 'devolvida_por_user_id');
        });

        Schema::table('conversas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['devolvida_por_user_id']);
            $table->dropColumn(['user_id', 'devolvida_por_user_id']);
            $table->foreignId('atendente_id')->nullable()->constrained('atendentes')->nullOnDelete();
            $table->foreignId('devolvida_por')->nullable()->constrained('atendentes')->nullOnDelete();
        });

        // Remover tabela user_account
        Schema::dropIfExists('user_account');

        // Remover campos de users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status_atendimento', 'max_conversas', 'conversas_ativas', 'ultimo_acesso']);
        });
    }
};
