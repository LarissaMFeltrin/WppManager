<?php

use yii\db\Migration;

class m260302_000001_multi_instance_support extends Migration
{
    public function safeUp()
    {
        // 1. Tabela junction: atendente <-> whatsapp_account (many-to-many)
        $this->createTable('atendente_account', [
            'id' => $this->primaryKey()->unsigned(),
            'atendente_id' => $this->integer()->notNull(),
            'account_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('uk_atendente_account', 'atendente_account', ['atendente_id', 'account_id'], true);
        $this->createIndex('idx_aa_account_id', 'atendente_account', 'account_id');

        // 2. Adicionar account_id direto na tabela conversas
        $this->addColumn('conversas', 'account_id', $this->integer()->null()->after('chat_id'));
        $this->createIndex('idx_conversas_account_id', 'conversas', 'account_id');

        // Backfill: preencher account_id a partir do chat vinculado
        $this->execute('UPDATE conversas c INNER JOIN chats ch ON ch.id = c.chat_id SET c.account_id = ch.account_id WHERE c.account_id IS NULL');

        // 3. Adicionar service_port na tabela whatsapp_accounts
        $this->addColumn('whatsapp_accounts', 'service_port', $this->integer()->null()->after('is_active'));
    }

    public function safeDown()
    {
        $this->dropColumn('whatsapp_accounts', 'service_port');
        $this->dropIndex('idx_conversas_account_id', 'conversas');
        $this->dropColumn('conversas', 'account_id');
        $this->dropTable('atendente_account');
    }
}
