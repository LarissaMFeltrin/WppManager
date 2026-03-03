<?php

use yii\db\Migration;

class m260226_000001_create_empresas_table extends Migration
{
    public function safeUp()
    {
        // Criar tabela empresas
        $this->createTable('empresas', [
            'id' => $this->primaryKey(),
            'nome' => $this->string(200)->notNull(),
            'cnpj' => $this->string(20)->null(),
            'telefone' => $this->string(20)->null(),
            'email' => $this->string(100)->null(),
            'logo' => $this->string(500)->null(),
            'status' => $this->tinyInteger()->defaultValue(1),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Adicionar empresa_id nas tabelas existentes
        $this->addColumn('whatsapp_accounts', 'empresa_id', $this->integer()->null()->after('id'));
        $this->addColumn('users', 'empresa_id', $this->integer()->null()->after('id'));
        $this->addColumn('atendentes', 'empresa_id', $this->integer()->null()->after('id'));

        // Inserir empresa padrão
        $this->insert('empresas', [
            'nome' => 'Scordon',
            'email' => 'contato@scordon.com.br',
        ]);

        // Vincular registros existentes à empresa padrão
        $this->update('whatsapp_accounts', ['empresa_id' => 1]);
        $this->update('users', ['empresa_id' => 1]);
        $this->update('atendentes', ['empresa_id' => 1]);

        // Foreign keys
        $this->addForeignKey('fk_whatsapp_accounts_empresa', 'whatsapp_accounts', 'empresa_id', 'empresas', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_users_empresa', 'users', 'empresa_id', 'empresas', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_atendentes_empresa', 'atendentes', 'empresa_id', 'empresas', 'id', 'SET NULL', 'CASCADE');

        // Índices
        $this->createIndex('idx_whatsapp_accounts_empresa', 'whatsapp_accounts', 'empresa_id');
        $this->createIndex('idx_users_empresa', 'users', 'empresa_id');
        $this->createIndex('idx_atendentes_empresa', 'atendentes', 'empresa_id');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_whatsapp_accounts_empresa', 'whatsapp_accounts');
        $this->dropForeignKey('fk_users_empresa', 'users');
        $this->dropForeignKey('fk_atendentes_empresa', 'atendentes');

        $this->dropColumn('whatsapp_accounts', 'empresa_id');
        $this->dropColumn('users', 'empresa_id');
        $this->dropColumn('atendentes', 'empresa_id');

        $this->dropTable('empresas');
    }
}
